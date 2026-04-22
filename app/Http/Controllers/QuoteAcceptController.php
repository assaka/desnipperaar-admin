<?php

namespace App\Http\Controllers;

use App\Mail\OrderCreated;
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

        $order->update([
            'quote_accepted_at'    => now(),
            'quote_acceptance_ip'  => $request->ip(),
            'type'                 => Order::TYPE_DIRECT,
        ]);

        if (!$order->bons()->exists()) {
            Bon::create([
                'bon_number' => Bon::generateBonNumber(),
                'order_id'   => $order->id,
                'mode'       => $order->delivery_mode,
            ]);
        }

        try {
            Mail::to($order->customer_email)->send(new OrderCreated($order));
        } catch (\Throwable $e) {
            report($e);
        }

        return view('public.quote-accepted', compact('order'));
    }
}
