<?php

namespace App\Http\Controllers;

use App\Mail\OrderCreated;
use App\Mail\SalesAlert;
use App\Models\Bon;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class QuoteAcceptController extends Controller
{
    public function show(string $token)
    {
        $order = Order::where('quote_token', $token)->firstOrFail();
        return view('public.quote', compact('order'));
    }

    public function accept(Request $request, string $token)
    {
        $order = Order::where('quote_token', $token)->firstOrFail();

        if ($order->quote_accepted_at) {
            return view('public.quote-already-accepted', compact('order'));
        }

        if ($order->isQuoteExpired()) {
            return view('public.quote-expired', compact('order'));
        }

        // The customer confirms the delivery address on the quote page before the
        // quote becomes an order. Validation failure bounces back to the quote page
        // (public.quote renders $errors + repopulates via old()).
        $data = $request->validate([
            'telefoon'   => 'required|string|max:50',
            'straat'     => 'required|string|max:255',
            'huisnummer' => 'required|string|max:20',
            'postcode'   => ['required', 'string', 'max:10', 'regex:/^\d{4}\s?[A-Za-z]{2}$/'],
            'stad'       => 'required|string|max:100',
        ], [
            'telefoon.required'   => 'Vul uw telefoonnummer in.',
            'straat.required'     => 'Vul de straatnaam in.',
            'huisnummer.required' => 'Vul het huisnummer in.',
            'postcode.required'   => 'Vul uw postcode in.',
            'postcode.regex'      => 'Vul een geldige postcode in, bijvoorbeeld 1034AB.',
            'stad.required'       => 'Vul de stad in.',
        ]);

        $postcode = strtoupper(preg_replace('/\s+/', '', $data['postcode']));

        // On acceptance: mint a new B- order number, keep the O- reference as audit
        // trail, and persist the delivery address the customer just confirmed.
        $order->update([
            'order_number'         => Order::generateOrderNumber(),
            'customer_phone'       => $data['telefoon'],
            'customer_address'     => trim($data['straat'] . ' ' . $data['huisnummer']),
            'customer_postcode'    => $postcode,
            'customer_city'        => $data['stad'],
            'quote_accepted_at'    => now(),
            'quote_acceptance_ip'  => $request->ip(),
            'type'                 => Order::TYPE_DIRECT,
        ]);

        // Bon is NOT created on accept — admin will plan the pickup and create the bon then.

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

        return view('public.quote-accepted', compact('order'));
    }
}
