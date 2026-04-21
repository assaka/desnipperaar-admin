<?php

namespace App\Http\Controllers;

use App\Mail\OrderCreated;
use App\Models\Bon;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::orderByDesc('id')->paginate(25);
        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        return view('orders.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'      => 'required|string|max:255',
            'customer_email'     => 'required|email',
            'customer_phone'     => 'nullable|string|max:50',
            'customer_address'   => 'nullable|string|max:255',
            'customer_postcode'  => 'nullable|string|max:10',
            'customer_city'      => 'nullable|string|max:100',
            'customer_reference' => 'nullable|string|max:100',
            'delivery_mode'      => 'required|in:ophaal,breng,mobiel',
            'box_count'          => 'nullable|integer|min:0',
            'container_count'    => 'nullable|integer|min:0',
            'notes'              => 'nullable|string|max:5000',
        ]);

        $postcode = preg_replace('/\s+/', '', strtoupper($data['customer_postcode'] ?? ''));
        $numeric  = (int) substr($postcode, 0, 4);
        $pilot    = $numeric >= config('desnipperaar.pilot.postcode_start')
                 && $numeric <= config('desnipperaar.pilot.postcode_end');

        $order = Order::create([
            ...$data,
            'customer_postcode' => $postcode ?: null,
            'box_count'         => $data['box_count']       ?? 0,
            'container_count'   => $data['container_count'] ?? 0,
            'order_number'      => Order::generateOrderNumber(),
            'state'             => Order::STATE_NIEUW,
            'pilot'             => $pilot,
        ]);

        // Auto-create a placeholder bon — driver fills in details at pickup.
        Bon::create([
            'bon_number' => Bon::generateBonNumber(),
            'order_id'   => $order->id,
            'mode'       => $order->delivery_mode,
        ]);

        try {
            Mail::to($order->customer_email)->send(new OrderCreated($order));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('orders.show', $order);
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
