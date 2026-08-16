<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderCreated;
use App\Mail\SalesAlert;
use App\Models\Bon;
use App\Models\Customer;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        // Honeypot — bot-filled field from the static form.
        if (filled($request->input('website'))) {
            return response()->json(['ok' => true], 201);
        }

        // Het bestelformulier stuurde de postcode jarenlang onder de naam
        // "plaats", terwijl "stad" de plaatsnaam droeg. Dezelfde sleutel betekent
        // op /api/subscription en /api/offerte juist wél een plaatsnaam, dus de
        // naam loog en per route iets anders. De site stuurt hem nu als
        // "postcode".
        //
        // Hier alleen normaliseren en niet hernoemen: zolang oude pagina's in een
        // browsercache nog "plaats" sturen moeten die blijven werken, en de rest
        // van deze controller blijft zo ongewijzigd. Weghalen kan zodra de logs
        // een tijd geen "plaats" zonder "postcode" meer laten zien.
        if (filled($request->input('postcode')) && blank($request->input('plaats'))) {
            $request->merge(['plaats' => $request->input('postcode')]);
        }

        $data = $request->validate([
            'naam'       => 'required|string|max:255',
            'bedrijf'    => 'nullable|string|max:255',
            'email'      => 'required|email',
            'telefoon'   => 'required|string|max:50',
            'adres'      => 'nullable|string|max:255',
            'straat'     => 'required|string|max:255',
            'huisnummer' => 'required|string|max:20',
            'stad'       => 'required|string|max:100',
            'plaats'     => 'required|string|max:10|regex:/^\d{4}\s?[A-Za-z]{2}$/',
            'branche'    => 'nullable|string|max:100',
            // Vrije attributiekeuze van het bestelformulier. Alleen ter informatie
            // in de notities, dus geen eigen kolom en geen vaste lijst.
            'gevonden_via' => 'nullable|string|max:100',
            'type'       => 'nullable|string|max:200',
            'volume'     => 'nullable|string|max:500',
            'locatie'    => 'nullable|string|max:200',
            'termijn'    => 'nullable|string|max:100',
            'bericht'    => 'nullable|string|max:5000',
            'akkoord'    => 'nullable',
            'boxes'          => 'nullable|integer|min:0|max:500',
            'containers'     => 'nullable|integer|min:0|max:50',
            'media_json'     => 'nullable|string|max:2000',
            'first_box_free' => 'nullable|in:0,1,true,false',
            'coupon_code'    => 'nullable|string|max:50',
            'lang'           => 'nullable|in:nl,en,fr,es',
            'ophaal_keuze'   => 'nullable|string|max:60',
            'ophaal_km'      => 'nullable|integer|min:0|max:400',
            'ophaal_kosten'  => 'nullable|numeric|min:0|max:1000',
        ], [], [
            // De regel heet intern nog 'plaats', maar het formulierveld heet
            // postcode. Zonder deze vertaling krijgt de bezoeker "The plaats
            // field is required" te zien bij een veld dat Postcode heet.
            'plaats' => 'postcode',
            'stad'   => 'plaats',
        ]);

        $locale = in_array($data['lang'] ?? null, ['nl', 'en', 'fr', 'es'], true) ? $data['lang'] : 'nl';

        // Postcode extraction — from "plaats" field which may contain city+postcode.
        // Capture digits + letters separately so we keep the full NL format (e.g. 1034AB).
        $postcode = null;
        if (preg_match('/\b(\d{4})\s?([A-Za-z]{0,2})\b/', $data['plaats'] ?? '', $m)) {
            $postcode = $m[1] . strtoupper($m[2] ?? '');
        }
        $numeric = (int) substr($postcode ?? '', 0, 4);
        $pilot   = config('desnipperaar.pilot.enabled')
                && $numeric >= config('desnipperaar.pilot.postcode_start')
                && $numeric <= config('desnipperaar.pilot.postcode_end');

        $loc = strtolower($data['locatie'] ?? '');
        $mode = str_contains($loc, 'brengen') ? 'breng'
              : (str_contains($loc, 'mobiel')  ? 'mobiel' : 'ophaal');

        // Pickup cost. The static site sends the road distance (ophaal_km) and the
        // chosen option (ophaal_keuze = 'spoed'|'sooner'|'free'); we recompute the
        // amount server-side so the client can never dictate the price.
        //
        // Spoed betaalt de kilometers net zo goed als "sooner", plus een vaste
        // toeslag. Het is een extra dienst bovenop het rijden en geen ander tarief
        // voor datzelfde rijden, vergelijkbaar met expreslevering.
        $pickupKm     = isset($data['ophaal_km']) && $data['ophaal_km'] !== '' ? (int) $data['ophaal_km'] : null;
        $choice       = $data['ophaal_keuze'] ?? '';
        $pickupChoice = in_array($choice, ['spoed', 'sooner'], true) ? $choice : 'free';

        // Mag spoed hier verkocht worden? In de regiostand alleen binnen de
        // gratis straal. Is de afstand onbekend, dan mag het niet: een toeslag
        // rekenen omdat een geocodering mislukte is de verkeerde kant om.
        //
        // Wat niet mag valt terug op 'sooner'. De klant wilde snel geholpen
        // worden en dat kan, alleen rekenen wij geen toeslag die wij hem daar
        // nergens hebben aangeboden.
        $rushMode = config('desnipperaar.pickup.rush_mode');
        $rushAllowed = match ($rushMode) {
            'all'    => true,
            'region' => $pickupKm !== null && $pickupKm <= \App\Support\Pricing::PICKUP_FREE_KM,
            default  => false,
        };

        if ($pickupChoice === 'spoed' && ! $rushAllowed) {
            $pickupChoice = 'sooner';
        }

        $pickupCost   = \App\Support\Pricing::pickupCost($pickupKm, $pickupChoice !== 'free');
        $pickupRushFee = \App\Support\Pricing::pickupRushFee($pickupChoice === 'spoed');

        // Volume line dropped — box_count / container_count / media_items carry the same info structurally.
        $notes = collect([
            !empty($data['bedrijf']) ? 'Bedrijf: '  . $data['bedrijf']  : null,
            !empty($data['branche']) ? 'Branche: '  . $data['branche']  : null,
            !empty($data['gevonden_via']) ? 'Gevonden via: ' . $data['gevonden_via'] : null,
            !empty($data['type'])    ? 'Type: '     . $data['type']     : null,
            !empty($data['termijn']) ? 'Termijn: '  . $data['termijn']  : null,
            !empty($data['bericht']) ? "\n"         . $data['bericht']  : null,
        ])->filter()->implode("\n");

        // Parse cart payload from the webshop-style /order page.
        $mediaItems = null;
        if (!empty($data['media_json'])) {
            $decoded = json_decode($data['media_json'], true);
            if (is_array($decoded)) {
                $mediaItems = array_filter($decoded, fn ($v) => is_numeric($v) && $v > 0);
            }
        }

        // Persist/update the customer record so they appear in the admin klanten-lijst.
        $customer = Customer::firstOrCreate(
            ['email' => strtolower(trim($data['email']))],
            [
                'name'     => $data['naam'],
                'company'  => $data['bedrijf'] ?? null,
                'phone'    => $data['telefoon'],
                'address'  => $data['adres']   ?? null,
                'postcode' => $postcode,
                'city'     => $data['stad']    ?? null,
                'branche'  => $data['branche'] ?? null,
                'locale'   => $locale,
            ]
        );
        // On subsequent orders, fill in any missing details we did not have before.
        $customer->fill(array_filter([
            'company'  => $customer->company  ?: ($data['bedrijf'] ?? null),
            'phone'    => $customer->phone    ?: $data['telefoon'],
            'address'  => $customer->address  ?: ($data['adres'] ?? null),
            'postcode' => $customer->postcode ?: $postcode,
            'city'     => $customer->city     ?: ($data['stad'] ?? null),
            'branche'  => $customer->branche  ?: ($data['branche'] ?? null),
            'locale'   => $locale,
        ]))->save();

        // De kortingscode uit het bestelformulier.
        //
        // Tot nu toe werd hier alleen times_used opgehoogd en landde de code
        // nergens: de klant zag zijn korting in het winkelwagentje, de order in de
        // admin wist er niets van en de factuur dus ook niet. Iemand moest hem met
        // de hand alsnog toekennen, en dat gebeurde soms dagen later of helemaal
        // niet.
        //
        // Twee dingen moeten kloppen met wat de klant op /order zag, anders belooft
        // het scherm iets anders dan de factuur:
        //
        //  - de grondslag is het bedrag van de goederen, zonder ophaalkosten. Het
        //    winkelwagentje trekt de korting van het artikelsubtotaal af en zet de
        //    ophaalkosten er daarna bovenop. Die rit is doorbelaste kilometers, geen
        //    dienst waar een actiecode korting op geeft.
        //  - een kortingscode vervangt de pilotkorting. Hoogstens één korting, dat
        //    is de regel op de pagina, dus wie een code invult krijgt de
        //    Amsterdam-korting niet er nog eens bovenop.
        $kennismaking = $this->isKennismakingEligible($data);

        $coupon         = ! empty($data['coupon_code']) ? Coupon::findByCode($data['coupon_code']) : null;
        $couponBase     = 0.0;
        $couponDiscount = 0.0;

        if ($coupon) {
            $couponBase = (float) \App\Support\Pricing::snapshot(
                (int) ($data['boxes'] ?? 0),
                (int) ($data['containers'] ?? 0),
                $mediaItems,
                false,          // pilot uit, de code neemt zijn plaats in
                $kennismaking,
                0.0,            // ophaalkosten tellen niet mee in de grondslag
                0.0,
            )['subtotal'];

            $couponDiscount = $coupon->isValid($couponBase)
                ? round($coupon->discountFor($couponBase), 2)
                : 0.0;

            // Een code die niets oplevert (verlopen, uitgeput, onder het
            // minimumbedrag of een lege order) hoort niet op de order te staan en
            // telt ook niet als gebruik.
            if ($couponDiscount <= 0) {
                $coupon = null;
            }
        }

        $order = Order::create([
            'order_number'       => Order::generateOrderNumber(),
            'customer_id'        => $customer->id,
            'customer_name'      => $data['naam'],
            'customer_email'     => $data['email'],
            'customer_phone'     => $data['telefoon'],
            'customer_address'   => $data['adres'] ?? null,
            'customer_postcode'  => $postcode,
            // Geen terugval op 'plaats' meer: dat veld draagt een postcode, geen
            // plaatsnaam, dus die terugval zou een postcode als stad opslaan.
            // 'stad' is verplicht, dus er valt niets terug te vallen.
            'customer_city'      => $data['stad'] ?? null,
            'locale'             => $locale,
            'customer_reference' => $customer->reference,
            'delivery_mode'      => $mode,
            'box_count'          => (int) ($data['boxes']      ?? 0),
            'container_count'    => (int) ($data['containers'] ?? 0),
            'media_items'        => $mediaItems ?: null,
            'notes'              => $notes ?: null,
            'state'              => Order::STATE_NIEUW,
            'pilot'              => $coupon ? false : $pilot,
            'first_box_free'     => $kennismaking,
            'pickup_cost'        => $pickupCost,
            'pickup_rush_fee'    => $pickupRushFee > 0 ? $pickupRushFee : null,
            'pickup_km'          => $pickupKm,
            'pickup_choice'      => $pickupChoice,
            'coupon_code'        => $coupon ? strtoupper(trim($coupon->code)) : null,
            'coupon_discount'    => $coupon ? $couponDiscount : null,
            'coupon_applied_at'  => $coupon ? now() : null,
            'coupon_type'        => $coupon?->type,
            'coupon_value'       => $coupon?->value,
            'coupon_base'        => $coupon ? $couponBase : null,
        ]);

        // Pas tellen als de code ook echt op een order staat. Een code die is
        // afgewezen mag geen tik van zijn maximum aantal gebruiken kosten.
        $coupon?->incrementUsage();

        // Bon is intentionally NOT created here — it's only created once admin plans the pickup.

        try {
            Mail::to($order->customer_email)->send(new OrderCreated($order));
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            Mail::send(new SalesAlert($order, 'new_order'));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'ok' => true,
            'order_number' => $order->order_number,
            'kennismaking_applied' => (bool) $order->first_box_free,
        ], 201);
    }

    /**
     * Kennismaking is granted only when:
     *  - customer requested it (first_box_free flag)
     *  - the email has not been seen before in any previous Order
     * Independent of pilot-korting.
     */
    private function isKennismakingEligible(array $data): bool
    {
        if (!($data['first_box_free'] ?? false)) return false;

        $email = strtolower(trim($data['email']));
        $customer = Customer::whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$customer) return true;  // new customer — eligible

        return !$customer->orders()->exists();
    }
}
