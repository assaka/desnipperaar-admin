<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderCreated;
use App\Models\Bon;
use App\Models\Customer;
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
        ]);

        // Postcode extraction — from "plaats" field which may contain city+postcode.
        $postcode = null;
        if (preg_match('/\b(\d{4})\s?[A-Za-z]{0,2}\b/', $data['plaats'] ?? '', $m)) {
            $postcode = $m[1] . (isset($m[2]) ? $m[2] : '');
        }
        $numeric = (int) substr($postcode ?? '', 0, 4);
        $pilot   = $numeric >= config('desnipperaar.pilot.postcode_start')
                && $numeric <= config('desnipperaar.pilot.postcode_end');

        $loc = strtolower($data['locatie'] ?? '');
        $mode = str_contains($loc, 'brengen') ? 'breng'
              : (str_contains($loc, 'mobiel')  ? 'mobiel' : 'ophaal');

        $notes = collect([
            !empty($data['bedrijf']) ? 'Bedrijf: '  . $data['bedrijf']  : null,
            !empty($data['branche']) ? 'Branche: '  . $data['branche']  : null,
            !empty($data['type'])    ? 'Type: '     . $data['type']     : null,
            !empty($data['volume'])  ? 'Volume: '   . $data['volume']   : null,
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
        ]))->save();

        $order = Order::create([
            'order_number'       => Order::generateOrderNumber(),
            'customer_id'        => $customer->id,
            'customer_name'      => $data['naam'],
            'customer_email'     => $data['email'],
            'customer_phone'     => $data['telefoon'],
            'customer_address'   => $data['adres'] ?? null,
            'customer_postcode'  => $postcode,
            'customer_city'      => $data['stad']   ?? $data['plaats'] ?? null,
            'customer_reference' => $customer->reference,
            'delivery_mode'      => $mode,
            'box_count'          => (int) ($data['boxes']      ?? 0),
            'container_count'    => (int) ($data['containers'] ?? 0),
            'media_items'        => $mediaItems ?: null,
            'notes'              => $notes ?: null,
            'state'              => Order::STATE_NIEUW,
            'pilot'              => $pilot,
            'first_box_free'     => $this->isKennismakingEligible($data),
        ]);

        Bon::create([
            'bon_number' => Bon::generateBonNumber(),
            'order_id'   => $order->id,
            'mode'       => $order->delivery_mode,
        ]);

        try {
            Mail::to($order->customer_email)->send(new OrderCreated($order));
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
