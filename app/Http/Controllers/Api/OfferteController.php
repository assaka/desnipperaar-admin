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
            'naam'      => 'required|string|max:255',
            'bedrijf'   => 'nullable|string|max:255',
            'email'     => 'required|email',
            'telefoon'  => 'required|string|max:50',
            'plaats'    => 'nullable|string|max:100',
            'branche'   => 'nullable|string|max:100',
            'volume'    => 'required|string|max:500',
            'bericht'   => 'nullable|string|max:5000',
        ]);

        $customer = Customer::firstOrCreate(
            ['email' => $data['email']],
            [
                'name'    => $data['naam'],
                'company' => $data['bedrijf'] ?? null,
                'phone'   => $data['telefoon'],
                'city'    => $data['plaats']  ?? null,
                'branche' => $data['branche'] ?? null,
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
            'customer_city'  => $customer->city,
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
