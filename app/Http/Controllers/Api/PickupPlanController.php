<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PickupConfirmed;
use App\Mail\PickupPlannedByCustomer;
use App\Models\Order;
use App\Services\PickupAssigner;
use App\Services\SlotFinder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * Token-gated JSON API achter de planpagina op desnipperaar.nl. De klant kiest
 * daar zelf zijn ophaalmoment uit de momenten die wij kunnen rijden.
 *
 * Het verschil met RescheduleController is wie het laatste woord heeft. Een
 * herplanning is een verzoek: de klant noemt een dag, wij kijken of het kan. Hier
 * kijken wij eerst en biedt de klant niets aan, hij kiest uit wat al kan. Daarom
 * staat de datum meteen op de order en gaat er een bevestiging uit, in plaats van
 * "wij laten het u weten".
 *
 * De pagina is bewust nog nergens vandaan gelinkt.
 */
class PickupPlanController extends Controller
{
    public function show(string $token, SlotFinder $finder): JsonResponse
    {
        $order = Order::where('public_token', $token)->first();
        if (! $order) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json($this->payload($order, $finder));
    }

    public function store(Request $request, string $token, SlotFinder $finder): JsonResponse
    {
        $order = Order::where('public_token', $token)->first();
        if (! $order) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if ($this->status($order) !== 'ok') {
            return response()->json(['error' => 'closed', 'status' => $this->status($order)], 403);
        }

        // Het venster is een uurblok, "12:00-13:00". De dagdelen staan er nog bij
        // voor het geval wij ze ooit weer aanbieden; zelfde vormen als de regex in
        // OrderController, zodat beide kanten dezelfde waarden accepteren.
        //
        // Deze regel stond hier nog op alleen dagdelen en wees daardoor elk
        // gekozen uur af. De klant kreeg dan "kies een van de momenten hierboven"
        // te zien terwijl hij er net een had gekozen.
        $validator = Validator::make($request->all(), [
            'date'   => 'required|date_format:Y-m-d',
            'window' => ['required', 'regex:/^(flexibel|ochtend|middag|avond|([01]\d|2[0-3]):00-([01]\d|2[0-3]):00)$/'],
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();

        // Opnieuw narekenen in plaats van de klant op zijn woord geloven. Tussen
        // het kiezen en het bevestigen kan er een andere ophaling zijn bijgekomen,
        // en dan is de dag vol of duurder geworden. Een 409 stuurt de pagina terug
        // met verse dagen.
        $result = $finder->forOrder($order);
        $slot = $finder->find($result['slots'], $data['date'], $data['window']);

        if (! $slot) {
            return response()->json([
                'error' => 'slot_taken',
                'days'  => $finder->days($result['slots']),
            ], 409);
        }

        // pickup_cost blijft met opzet ongemoeid. Die prijs is bij het bestellen
        // uit het adres berekend en staat in de orderbevestiging; welke dag de
        // klant kiest verandert daar niets aan. Zou de dagkeuze de prijs bepalen,
        // dan hing de rekening af van hoe vol de agenda toevallig stond op het
        // moment van boeken, en dat is geen prijs die je kunt verdedigen.
        //
        // Ook een spoedtoeslag komt hier niet vandaan. Wat spoed kost hoort bij
        // het bestellen, waar de klant de keuze maakt en de prijs ziet voordat hij
        // afrekent. Een toeslag die pas op de planpagina verschijnt is een prijs
        // die na het afrekenen omhoog gaat.
        //
        // Stond er al een moment, dan onthouden wij het oude. Voor de interne
        // melding is dat het verschil tussen een nieuwe boeking en een verzetting,
        // en bij een verzetting komt er een dag vrij waar jij iets mee kunt.
        $previous = $order->pickup_date
            ? trim($order->pickup_date->format('d-m-Y').' '.($order->pickup_window ?? ''))
            : null;

        // Rijdt er maar één chauffeur, dan hangen wij hem er meteen aan en is de
        // rit bevestigd. Er valt niets te kiezen, dus een order die blijft wachten
        // op "wijs een chauffeur toe" wacht op een beslissing die al vaststaat.
        //
        // Zijn er meer chauffeurs, of geen enkele, dan blijft de order op nieuw
        // staan met alleen een datum. Dan is toewijzen gokken, en dat doet een
        // mens beter. Zelfde regel als het lijstje op de orderpagina.
        $driver = PickupAssigner::soleDriver();

        $patch = [
            'pickup_date'                   => $data['date'],
            'pickup_window'                 => $data['window'],
            'pickup_planned_by_customer_at' => now(),
        ];

        if ($driver) {
            PickupAssigner::attach($order, $driver);
            $patch['state'] = Order::STATE_BEVESTIGD;
        }

        $order->update($patch);

        $fresh = $order->fresh()->load('customer');

        // Eén bevestiging, ook als wij hier meteen een chauffeur toewijzen. De
        // klant hoeft niet te weten dat er intern nog een stap zat.
        try {
            Mail::to($order->customer_email)->send(new PickupConfirmed($fresh));
        } catch (\Throwable $e) {
            report($e);
        }

        $adminEmail = config('desnipperaar.notifications.admin_email');
        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new PickupPlannedByCustomer($fresh, $previous, $driver?->name));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'ok'     => true,
            'pickup' => [
                'date'   => $fresh->pickup_date?->toDateString(),
                'window' => $fresh->pickup_window,
            ],
        ]);
    }

    private function payload(Order $order, SlotFinder $finder): array
    {
        $status = $this->status($order);

        // Alleen zoeken als er iets te kiezen valt. Bij een opgehaalde of
        // afgesloten order is de lijst niet alleen nutteloos, hij kost ook een
        // ronde geocoderen.
        $days = [];
        $best = [];
        $depotKm = null;
        if ($status === 'ok') {
            $result = $finder->forOrder($order);
            $days = $finder->days($result['slots']);
            $best = $finder->bestSlots($result['slots'], soonestFirst: $order->pickup_choice === 'spoed');
            $depotKm = $result['depot_km'];
        }

        return [
            'order_number'      => $order->order_number,
            'locale'            => in_array($order->locale, ['nl', 'en', 'fr', 'es'], true) ? $order->locale : 'nl',
            'status'            => $status,
            'can_plan'          => $status === 'ok',
            'pickup_date'       => $order->pickup_date?->toDateString(),
            'pickup_window'     => $order->pickup_window,
            'customer_postcode' => $order->customer_postcode,
            'customer_city'     => $order->customer_city,
            'depot_km'          => $depotKm,
            // De ophaalprijs zoals die bij het bestellen is berekend. Eén bedrag
            // voor alle dagen, puur ter herinnering op de pagina: de dagkeuze
            // verandert hem niet.
            'pickup_cost'       => (float) ($order->pickup_cost ?? 0),
            // De drie momenten die wij voorstellen, met een concreet uur erbij.
            // Past er niets, dan is bellen sneller dan door alle vrije uren van
            // vier weken scrollen; de pagina zegt dat er ook bij.
            'best'              => $best,
            'days'              => $days,
        ];
    }

    /**
     * Waarom de klant wel of niet kan plannen.
     *
     * Een abonnement heeft een eigen ritme met vaste ophaaldagen, daar valt niets
     * los te kiezen.
     *
     * Een bestaande afspraak is géén reden om te weigeren. Wijzigen ging vroeger
     * via de herplanpagina, waar de klant een dag voorstelde en wij binnen een
     * werkdag lieten weten of het kon. Hier weten wij het meteen, dus is dat een
     * omweg geworden: wie zijn moment wil verzetten kiest gewoon een ander uit
     * dezelfde lijst. Eén plek, en een antwoord in plaats van een verzoek.
     *
     * De dag zelf is te laat. Dan staat de bus geladen en is bellen sneller dan
     * een formulier.
     */
    private function status(Order $order): string
    {
        if ($order->isAbonnement()) {
            return 'subscription';
        }
        if (in_array($order->state, [Order::STATE_OPGEHAALD, Order::STATE_VERNIETIGD], true)) {
            return 'picked_up';
        }
        if ($order->state === Order::STATE_AFGESLOTEN) {
            return 'closed';
        }
        if ($order->pickup_date && $order->pickup_date->toDateString() <= now()->toDateString()) {
            return 'too_late';
        }

        return 'ok';
    }
}
