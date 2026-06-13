<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\RescheduleRequested;
use App\Mail\RescheduleRequestedAdmin;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * Token-gated JSON API backing the public reschedule page on desnipperaar.nl.
 * The customer-facing HTML lives on the public Node site; this controller only
 * exposes the order data and records the reschedule request (incl. e-mails).
 */
class RescheduleController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $order = Order::where('public_token', $token)->first();
        if (!$order) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json($this->payload($order));
    }

    public function store(Request $request, string $token): JsonResponse
    {
        $order = Order::where('public_token', $token)->first();
        if (!$order) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if (!$this->canReschedule($order)) {
            return response()->json(['error' => 'closed', 'status' => $this->status($order)], 403);
        }

        $validator = Validator::make($request->all(), [
            'new_date'   => 'required|date|after:today',
            'new_window' => 'required|in:ochtend,middag,avond,flexibel',
            'notes'      => 'nullable|string|max:2000',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();

        $order->update([
            'reschedule_requested_at'     => now(),
            'reschedule_requested_date'   => $data['new_date'],
            'reschedule_requested_window' => $data['new_window'],
            'reschedule_notes'            => $data['notes'] ?? null,
        ]);

        $fresh = $order->fresh();
        $admin = $order->senderUser();

        try {
            Mail::to($order->customer_email)->send(new RescheduleRequested($fresh, $admin));
        } catch (\Throwable $e) {
            report($e);
        }

        if ($admin?->email && strcasecmp($admin->email, $order->customer_email) !== 0) {
            try {
                Mail::to($admin->email)->send(new RescheduleRequestedAdmin($fresh));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'ok'         => true,
            'reschedule' => [
                'date'         => $fresh->reschedule_requested_date?->toDateString(),
                'window'       => $fresh->reschedule_requested_window,
                'requested_at' => $fresh->reschedule_requested_at?->toIso8601String(),
            ],
        ]);
    }

    private function payload(Order $order): array
    {
        return [
            'order_number'         => $order->order_number,
            'locale'               => in_array($order->locale, ['nl', 'en'], true) ? $order->locale : 'nl',
            'state'                => $order->state,
            'pickup_date'          => $order->pickup_date?->toDateString(),
            'pickup_window'        => $order->pickup_window,
            'customer_postcode'    => $order->customer_postcode,
            'customer_city'        => $order->customer_city,
            'status'               => $this->status($order),
            'can_reschedule'       => $this->canReschedule($order),
            'reschedule_requested' => $order->reschedule_requested_at ? [
                'date'         => $order->reschedule_requested_date?->toDateString(),
                'window'       => $order->reschedule_requested_window,
                'requested_at' => $order->reschedule_requested_at?->toIso8601String(),
            ] : null,
        ];
    }

    // Maps order state to a coarse status the public page renders alerts from.
    private function status(Order $order): string
    {
        if (in_array($order->state, [Order::STATE_OPGEHAALD, Order::STATE_VERNIETIGD], true)) {
            return 'picked_up';
        }
        if ($order->state === Order::STATE_AFGESLOTEN) {
            return 'closed';
        }
        if ($order->state !== Order::STATE_BEVESTIGD) {
            return 'not_confirmed';
        }
        if (!$order->pickup_date || $order->pickup_date->toDateString() <= now()->toDateString()) {
            return 'too_late';
        }
        return 'ok';
    }

    // Customer can reschedule until the pickup day itself.
    private function canReschedule(Order $order): bool
    {
        return $order->state === Order::STATE_BEVESTIGD
            && $order->pickup_date
            && $order->pickup_date->toDateString() > now()->toDateString();
    }
}
