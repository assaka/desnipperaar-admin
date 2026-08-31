<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OfferteRequest;
use App\Mail\QuoteRequested;
use App\Mail\SalesAlert;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class OfferteController extends Controller
{
    public function store(OfferteRequest $request)
    {
        if (filled($request->input('website'))) {
            return response()->json(['ok' => true], 201);
        }

        $data = $request->validated();

        $locale = in_array($data['lang'] ?? null, ['nl', 'en', 'fr', 'es'], true) ? $data['lang'] : 'nl';

        $postcode = null;
        if (preg_match('/\b(\d{4})\s?([A-Za-z]{0,2})\b/', $data['plaats'] ?? '', $m)) {
            $postcode = $m[1] . strtoupper($m[2] ?? '');
        }

        $customer = Customer::firstOrCreate(
            ['email' => strtolower(trim($data['email']))],
            [
                'name'     => $data['naam'],
                'company'  => $data['bedrijf']  ?? null,
                'phone'    => $data['telefoon'],
                'address'  => $data['adres']    ?? null,
                'postcode' => $postcode,
                'city'     => $data['stad']     ?? null,
                'branche'  => $data['branche']  ?? null,
                'locale'   => $locale,
            ]
        );

        $notes = collect([
            'Type: offerte op maat',
            !empty($data['bedrijf']) ? 'Bedrijf: '       . $data['bedrijf']  : null,
            !empty($data['branche']) ? 'Branche: '       . $data['branche']  : null,
            !empty($data['gevonden_via']) ? 'Gevonden via: ' . $data['gevonden_via'] : null,
            !empty($data['type'])    ? 'Materiaal: '     . $data['type']     : null,
            !empty($data['volume'])  ? 'Volume: '        . $data['volume']   : null,
            !empty($data['methode']) ? 'Methode: '       . $data['methode']  : null,
            !empty($data['termijn']) ? 'Termijn: '       . $data['termijn']  : null,
            $this->transportNotes($data),
            !empty($data['bericht']) ? "\n"              . $data['bericht']  : null,
        ])->filter()->implode("\n");

        // Transport A -> B blijft 'ophaal': het materiaal komt bij ons via een rit
        // op locatie A. De kolom is een enum ['ophaal','breng','mobiel'], dus een
        // eigen waarde zou een migratie vragen zonder iets toe te voegen. De
        // dienst is herkenbaar aan 'Methode: transport' in de notities.
        $deliveryMode = match (strtolower($data['methode'] ?? '')) {
            'brengen'            => 'breng',
            'mobiel',
            'mobiel-wachtlijst'  => 'mobiel',
            default              => 'ophaal',
        };

        $quoteRef = Order::generateQuoteReference();
        $order = Order::create([
            'order_number'      => $quoteRef,
            'quote_reference'   => $quoteRef,
            'type'              => Order::TYPE_QUOTE,
            'customer_id'       => $customer->id,
            'customer_name'     => $customer->name,
            'customer_email'    => $customer->email,
            'customer_phone'    => $customer->phone,
            'customer_address'  => $customer->address,
            'customer_postcode' => $customer->postcode,
            'customer_city'     => $customer->city,
            'delivery_mode'     => $deliveryMode,
            'notes'             => $notes,
            'state'             => Order::STATE_NIEUW,
            'locale'            => $locale,
        ]);

        try {
            Mail::to($order->customer_email)->send(new QuoteRequested($order));
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            Mail::send(new SalesAlert($order, 'quote_request'));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'ok' => true,
            'order_number' => $order->order_number,
        ], 201);
    }

    /**
     * Het transportblok van het offerteformulier, als leesbaar blok in de
     * notities. Geeft null terug zodra er geen transportvelden zijn ingevuld,
     * zodat een gewone vernietigingsofferte er niets van merkt.
     *
     * De gemachtigde ontvanger is het enige veld dat de planning echt nodig
     * heeft: aan niemand anders mag afgeleverd worden.
     */
    private function transportNotes(array $data): ?string
    {
        $slotLabels = [
            'standaard' => 'standaard, code door de klant zelf ingesteld',
            'tracking'  => '4G-slot met live tracking (meerprijs)',
        ];

        $rows = collect([
            !empty($data['transport_van'])       ? 'Van: '   . $data['transport_van']  : null,
            !empty($data['transport_naar'])      ? 'Naar: '  . $data['transport_naar'] : null,
            !empty($data['transport_ontvanger']) ? 'Gemachtigde ontvanger: ' . $data['transport_ontvanger'] : null,
            !empty($data['transport_ontvanger_email']) ? 'E-mail ontvanger: ' . $data['transport_ontvanger_email'] : null,
            !empty($data['transport_colli'])     ? 'Aantal colli: ' . $data['transport_colli'] : null,
            !empty($data['transport_datum'])     ? 'Gewenste datum: ' . $data['transport_datum'] : null,
            !empty($data['transport_slot'])      ? 'Slot: ' . ($slotLabels[$data['transport_slot']] ?? $data['transport_slot']) : null,
        ])->filter();

        if ($rows->isEmpty()) {
            return null;
        }

        return "\nTransport A -> B\n" . $rows->implode("\n");
    }
}
