<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\QuoteRequested;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OfferteController extends Controller
{
    public function store(Request $request)
    {
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
            'volume'     => 'required|string|max:500',
            'bericht'    => 'nullable|string|max:5000',
            'boxes'      => 'nullable|integer|min:0|max:500',
            'containers' => 'nullable|integer|min:0|max:50',
            'media_json' => 'nullable|string|max:2000',
        ]);

        $postcode = null;
        if (preg_match('/\b(\d{4})\s?([A-Za-z]{0,2})\b/', $data['plaats'] ?? '', $m)) {
            $postcode = $m[1] . strtoupper($m[2] ?? '');
        }

        $customer = Customer::firstOrCreate(
            ['email' => $data['email']],
            [
                'name'     => $data['naam'],
                'company'  => $data['bedrijf'] ?? null,
                'phone'    => $data['telefoon'],
                'address'  => $data['adres']   ?? null,
                'postcode' => $postcode,
                'city'     => $data['stad']    ?? $data['plaats'] ?? null,
                'branche'  => $data['branche'] ?? null,
            ]
        );

        $notes = collect([
            'Type: offerte op maat',
            !empty($data['bedrijf']) ? 'Bedrijf: '  . $data['bedrijf']  : null,
            !empty($data['branche']) ? 'Branche: '  . $data['branche']  : null,
            'Volume-indicatie: ' . $data['volume'],
            !empty($data['bericht']) ? "\n" . $data['bericht'] : null,
        ])->filter()->implode("\n");

        $quoteRef = Order::generateQuoteReference();
        $order = Order::create([
            'order_number'   => $quoteRef,  // displayed while type=quote
            'quote_reference' => $quoteRef,
            'type'           => Order::TYPE_QUOTE,
            'customer_id'    => $customer->id,
            'customer_name'  => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'customer_address'  => $customer->address,
            'customer_postcode' => $customer->postcode,
            'customer_city'     => $customer->city,
            'delivery_mode'  => 'ophaal',
            'notes'          => $notes,
            'state'          => Order::STATE_NIEUW,
        ]);

        // No bon auto-created — admin sends custom offerte first, THEN creates bon after acceptance.

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
