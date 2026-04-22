<?php

namespace App\Http\Controllers;

use App\Mail\OrderCreated;
use App\Models\Bon;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('customer')->orderByDesc('id')->paginate(25);
        return view('orders.index', compact('orders'));
    }

    public function create(Request $request)
    {
        $preselected = null;
        if ($request->filled('customer')) {
            $c = Customer::find($request->integer('customer'));
            if ($c) {
                $preselected = [
                    'id' => $c->id, 'name' => $c->name, 'company' => $c->company,
                    'email' => $c->email, 'phone' => $c->phone,
                    'address' => $c->address, 'postcode' => $c->postcode,
                    'city' => $c->city, 'reference' => $c->reference,
                ];
            }
        }
        $drivers = \App\Models\Driver::active()->orderBy('name')->get(['id','name','license_last4']);
        return view('orders.create', compact('preselected', 'drivers'));
    }

    public function store(Request $request)
    {
        $rules = [
            'customer_id'    => 'nullable|exists:customers,id',
            'delivery_mode'  => 'required|in:ophaal,breng,mobiel',
            'box_count'      => 'nullable|integer|min:0',
            'container_count'=> 'nullable|integer|min:0',
            'pickup_date'    => 'nullable|date|after_or_equal:today',
            'pickup_window'  => 'nullable|in:ochtend,middag,avond,flexibel',
            'first_box_free' => 'nullable|boolean',
            'notes'          => 'nullable|string|max:5000',
            'driver_id'      => 'nullable|exists:drivers,id',
        ];

        // Only validate inline-new-customer fields when no existing customer selected.
        if (blank($request->input('customer_id'))) {
            $rules['new_customer.name']     = 'required|string|max:255';
            $rules['new_customer.email']    = 'required|email';
            $rules['new_customer.company']  = 'nullable|string|max:255';
            $rules['new_customer.phone']    = 'nullable|string|max:50';
            $rules['new_customer.address']  = 'nullable|string|max:255';
            $rules['new_customer.postcode'] = ['nullable','string','max:10','regex:/^\d{4}\s?[A-Za-z]{2}$/'];
            $rules['new_customer.city']     = 'nullable|string|max:100';
        }

        $validated = $request->validate($rules, [
            'new_customer.postcode.regex' => 'Postcode moet NL-formaat zijn (bv. 1034 AB).',
        ]);

        $customer = $validated['customer_id'] ?? null
            ? Customer::find($validated['customer_id'])
            : Customer::firstOrCreate(
                ['email' => $validated['new_customer']['email']],
                [
                    'name'     => $validated['new_customer']['name'],
                    'company'  => $validated['new_customer']['company']  ?? null,
                    'phone'    => $validated['new_customer']['phone']    ?? null,
                    'address'  => $validated['new_customer']['address']  ?? null,
                    'postcode' => strtoupper(preg_replace('/\s+/', '', $validated['new_customer']['postcode'] ?? '')) ?: null,
                    'city'     => $validated['new_customer']['city']     ?? null,
                ]
            );

        $postcode = $customer->postcode;
        $numeric  = (int) substr($postcode ?? '', 0, 4);
        $pilot    = $numeric >= config('desnipperaar.pilot.postcode_start')
                 && $numeric <= config('desnipperaar.pilot.postcode_end');

        $order = Order::create([
            'order_number'       => Order::generateOrderNumber(),
            'customer_id'        => $customer->id,
            'created_by_user_id' => $request->user()?->id,
            'customer_name'      => $customer->name,
            'customer_email'     => $customer->email,
            'customer_phone'     => $customer->phone,
            'customer_address'   => $customer->address,
            'customer_postcode'  => $postcode,
            'customer_city'      => $customer->city,
            'customer_reference' => $customer->reference,
            'delivery_mode'      => $validated['delivery_mode'],
            'box_count'          => $validated['box_count']       ?? 0,
            'container_count'    => $validated['container_count'] ?? 0,
            'pickup_date'        => $validated['pickup_date']     ?? null,
            'pickup_window'      => $validated['pickup_window']   ?? null,
            'notes'              => $validated['notes']           ?? null,
            'state'              => Order::STATE_NIEUW,
            'pilot'              => $pilot,
            'first_box_free'     => (bool) ($validated['first_box_free'] ?? false),
        ]);

        $driver = !empty($validated['driver_id'])
            ? \App\Models\Driver::find($validated['driver_id'])
            : null;

        Bon::create([
            'bon_number' => Bon::generateBonNumber(),
            'order_id'   => $order->id,
            'mode'       => $order->delivery_mode,
            'driver_id'  => $driver?->id,
            'driver_name_snapshot' => $driver?->name,
            'driver_license_last4' => $driver?->license_last4,
        ]);

        try {
            Mail::to($order->customer_email)->send(new OrderCreated($order, $request->user()));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('orders.show', $order);
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'createdBy', 'bons.driver', 'bons.seals', 'certificate']);
        $availableTransitions = $this->nextStates($order->state);
        $quote = \App\Support\Pricing::quote(
            $order->box_count,
            $order->container_count,
            (bool) $order->pilot,
            (bool) $order->first_box_free,
        );
        $hasSignedBon = $order->bons->whereNotNull('picked_up_at')->isNotEmpty();
        return view('orders.show', compact('order', 'availableTransitions', 'quote', 'hasSignedBon'));
    }

    public function transition(Request $request, Order $order)
    {
        $to = $request->string('to');
        abort_unless(in_array($to, $this->nextStates($order->state)), 422, 'Invalid transition');
        $order->update(['state' => $to]);
        return back();
    }

    public function mail(Request $request, Order $order)
    {
        $data = $request->validate([
            'to' => 'nullable|email',
        ]);
        $to = $data['to'] ?? $order->customer_email;

        try {
            // Sender defaults to the order's creator — consistent no matter who clicks resend.
            Mail::to($to)->send(new OrderCreated($order));
            return back()->with('status', "Bevestiging verzonden naar {$to}.");
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['mail' => 'Kon mail niet versturen: ' . $e->getMessage()]);
        }
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
