<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class RescheduleController extends Controller
{
    public function show(string $token)
    {
        $order = Order::where('public_token', $token)->firstOrFail();
        $canReschedule = $this->canReschedule($order);
        return view('public.reschedule', compact('order', 'canReschedule'));
    }

    public function store(Request $request, string $token)
    {
        $order = Order::where('public_token', $token)->firstOrFail();
        abort_unless($this->canReschedule($order), 403, 'Online herplannen is niet meer mogelijk — bel 06-10229965.');

        $data = $request->validate([
            'new_date'   => 'required|date|after:today',
            'new_window' => 'required|in:ochtend,middag,avond,flexibel',
            'notes'      => 'nullable|string|max:2000',
        ], [
            'new_date.after' => 'Kies een datum van morgen of later.',
        ]);

        $order->update([
            'reschedule_requested_at'     => now(),
            'reschedule_requested_date'   => $data['new_date'],
            'reschedule_requested_window' => $data['new_window'],
            'reschedule_notes'            => $data['notes'] ?? null,
        ]);

        return view('public.reschedule-received', compact('order'));
    }

    // Customer can reschedule until the pickup day itself. On the day of pickup (or after) the window is closed.
    private function canReschedule(Order $order): bool
    {
        return $order->state === Order::STATE_BEVESTIGD
            && $order->pickup_date
            && $order->pickup_date->toDateString() > now()->toDateString();
    }
}
