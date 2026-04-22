<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class RescheduleController extends Controller
{
    public function show(string $token)
    {
        $order = Order::where('public_token', $token)->firstOrFail();
        return view('public.reschedule', compact('order'));
    }

    public function store(Request $request, string $token)
    {
        $order = Order::where('public_token', $token)->firstOrFail();

        $data = $request->validate([
            'new_date'   => 'required|date|after_or_equal:today',
            'new_window' => 'required|in:ochtend,middag,avond,flexibel',
            'notes'      => 'nullable|string|max:2000',
        ]);

        $order->update([
            'reschedule_requested_at'     => now(),
            'reschedule_requested_date'   => $data['new_date'],
            'reschedule_requested_window' => $data['new_window'],
            'reschedule_notes'            => $data['notes'] ?? null,
        ]);

        return view('public.reschedule-received', compact('order'));
    }
}
