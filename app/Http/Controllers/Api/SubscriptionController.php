<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionRequest;
use App\Mail\SalesAlert;
use App\Mail\SubscriptionRequested;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

/**
 * Abonnementsaanvraag vanaf desnipperaar.nl (order.html, mode=abonnement).
 *
 * Spiegelt OfferteController: dezelfde publieke POST, dezelfde honeypot,
 * dezelfde Customer::firstOrCreate, en een order met een eigen type zodat de
 * aanvraag in dezelfde wachtrij en dezelfde offerte-machinerie valt.
 *
 * Wachtlijstaanvragen horen hier NIET. Ligt de postcode buiten het werkgebied,
 * dan post het formulier naar de Node-kant, die alle wachtlijsten van alle
 * diensten in één bestand bijhoudt. Zo blijft die lijst compleet en komt er
 * hier niets in de lijst te staan wat niemand kan beantwoorden.
 */
class SubscriptionController extends Controller
{
    public function store(SubscriptionRequest $request)
    {
        if (filled($request->input('website'))) {
            return response()->json(['ok' => true], 201);
        }

        $data = $request->validated();

        $locale = in_array($data['lang'] ?? null, ['nl', 'en', 'fr', 'es'], true) ? $data['lang'] : 'nl';

        $postcode = strtoupper(preg_replace('/\s+/', '', $data['postcode']));

        $customer = Customer::firstOrCreate(
            ['email' => strtolower(trim($data['email']))],
            [
                'name'     => $data['naam'],
                'company'  => $data['bedrijf']  ?? null,
                'phone'    => $data['telefoon'] ?? null,
                'address'  => $data['adres']    ?? null,
                'postcode' => $postcode,
                'city'     => $data['plaats']   ?? null,
                'locale'   => $locale,
            ]
        );

        // Looptijd, frequentie en prijs krijgen eigen kolommen. In notes staat
        // alleen wat de klant zelf heeft getypt, plus de waarschuwing hieronder.
        $notes = collect([
            'Type: abonnement, 240 L rolcontainer',
            // Hoort normaal niet te gebeuren: het formulier stuurt wachtlijst-
            // aanvragen naar de Node-kant. Komt er toch één binnen, dan moet dat
            // opvallen in plaats van stilletjes als boekbare aanvraag te landen.
            !empty($data['waitlist']) ? 'LET OP: gemarkeerd als wachtlijst (buiten werkgebied). Niets toezeggen over een startdatum.' : null,
            !empty($data['afstand_km']) ? 'Afstand: ± ' . $data['afstand_km'] . ' km' : null,
            !empty($data['opmerking']) ? "\n" . $data['opmerking'] : null,
        ])->filter()->implode("\n");

        $price = config("desnipperaar.subscription.prices.{$data['term']}.{$data['freq']}");

        $ref = Order::generateSubscriptionReference();
        $order = Order::create([
            'order_number'       => $ref,
            'quote_reference'    => $ref,
            'type'               => Order::TYPE_ABONNEMENT,
            'customer_id'        => $customer->id,
            'customer_name'      => $data['naam'],
            'customer_email'     => strtolower(trim($data['email'])),
            'customer_phone'     => $data['telefoon'] ?? null,
            'customer_address'   => $data['adres']    ?? null,
            'customer_postcode'  => $postcode,
            'customer_city'      => $data['plaats']   ?? null,
            'delivery_mode'      => 'ophaal',
            'container_count'    => 1,
            'sub_term'           => $data['term'],
            'sub_freq'           => $data['freq'],
            'sub_price_excl_btw' => $price,
            'notes'              => $notes,
            'state'              => Order::STATE_NIEUW,
            'locale'             => $locale,
        ]);

        try {
            Mail::to($order->customer_email)->send(new SubscriptionRequested($order));
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            Mail::send(new SalesAlert($order, 'subscription_request'));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'ok' => true,
            'order_number' => $order->order_number,
        ], 201);
    }
}
