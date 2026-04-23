<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OfferteRequest;
use App\Mail\QuoteRequested;
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
            ]
        );

        $notes = collect([
            'Type: offerte op maat',
            !empty($data['bedrijf']) ? 'Bedrijf: '       . $data['bedrijf']  : null,
            !empty($data['branche']) ? 'Branche: '       . $data['branche']  : null,
            !empty($data['type'])    ? 'Materiaal: '     . $data['type']     : null,
            !empty($data['volume'])  ? 'Volume: '        . $data['volume']   : null,
            !empty($data['methode']) ? 'Methode: '       . $data['methode']  : null,
            !empty($data['termijn']) ? 'Termijn: '       . $data['termijn']  : null,
            !empty($data['bericht']) ? "\n"              . $data['bericht']  : null,
        ])->filter()->implode("\n");

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
        ]);

        try {
            Mail::to($order->customer_email)->send(new QuoteRequested($order));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'ok' => true,
            'order_number' => $order->order_number,
        ], 201);
    }
}
