<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::orderByDesc('id')->paginate(25);
        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['bons.driver', 'certificate']);
        $availableTransitions = $this->nextStates($order->state);
        return view('orders.show', compact('order', 'availableTransitions'));
    }

    public function transition(Request $request, Order $order)
    {
        $to = $request->string('to');
        abort_unless(in_array($to, $this->nextStates($order->state)), 422, 'Invalid transition');

        $order->update(['state' => $to]);

        activity()
            ->performedOn($order)
            ->causedBy($request->user())
            ->withProperties(['from' => $order->getOriginal('state'), 'to' => $to])
            ->log('state_changed');

        return back();
    }

    private function nextStates(string $current): array
    {
        return match ($current) {
            Order::STATE_NIEUW       => [Order::STATE_BEVESTIGD],
            Order::STATE_BEVESTIGD   => [Order::STATE_OPGEHAALD],
            Order::STATE_OPGEHAALD   => [Order::STATE_VERNIETIGD],
            Order::STATE_VERNIETIGD  => [Order::STATE_AFGESLOTEN],
            Order::STATE_AFGESLOTEN  => [],
            default                  => [],
        };
    }
}
