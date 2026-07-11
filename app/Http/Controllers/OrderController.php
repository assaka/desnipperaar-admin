<?php

namespace App\Http\Controllers;

use App\Mail\OrderCreated;
use App\Mail\PickupConfirmed;
use App\Mail\QuoteSent;
use App\Models\Bon;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('customer')
            ->where('type', Order::TYPE_DIRECT)
            ->orderByDesc('id')
            ->paginate(25);
        return view('orders.index', compact('orders'));
    }

    public function offertes()
    {
        $offertes = Order::with('customer')
            ->where('type', Order::TYPE_QUOTE)
            ->orderByDesc('id')
            ->paginate(25);
        return view('offertes.index', compact('offertes'));
    }

    // Open customer reschedule requests (cleared when a new pickup is confirmed).
    public function reschedules()
    {
        $orders = Order::with('customer')
            ->whereNotNull('reschedule_requested_at')
            ->orderByDesc('reschedule_requested_at')
            ->paginate(25);
        return view('orders.reschedules', compact('orders'));
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
            'pickup_window'  => ['nullable', 'regex:/^(flexibel|ochtend|middag|avond|([01]\d|2[0-3]):00-([01]\d|2[0-3]):00)$/'],
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
        $pilot    = config('desnipperaar.pilot.enabled')
                 && $numeric >= config('desnipperaar.pilot.postcode_start')
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

        // Bon is NOT created here — created during the "Plan ophaling" step.
        // (The driver_id field on the form, if any, is saved in notes for the planner.)

        try {
            Mail::to($order->customer_email)->send(new OrderCreated($order, $request->user()));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('orders.show', $order);
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'createdBy', 'bons.driver', 'bons.seals', 'certificate', 'invoices']);
        $drivers = Driver::active()->orderBy('name')->get(['id','name','license_last4','signature_path']);
        $availableTransitions = $this->nextStates($order->state);

        $mediaPrices = ['hdd' => 9, 'ssd' => 15, 'usb' => 6, 'phone' => 12, 'laptop' => 19];
        $mediaLabels = ['hdd' => 'HDD', 'ssd' => 'SSD / NVMe', 'usb' => 'USB / SD', 'phone' => 'Telefoon / tablet', 'laptop' => 'Laptop'];

        $buildQuote = function ($boxes, $containers, $media) use ($order, $mediaPrices, $mediaLabels) {
            $q = \App\Support\Pricing::quote((int) $boxes, (int) $containers, (bool) $order->pilot, (bool) $order->first_box_free);
            foreach ((array) $media as $k => $qty) {
                $qty = (int) $qty;
                if ($qty > 0 && isset($mediaPrices[$k])) {
                    $q['lines'][] = ['label' => $mediaLabels[$k], 'qty' => $qty, 'unit' => $mediaPrices[$k], 'subtotal' => $mediaPrices[$k] * $qty];
                }
            }
            $pickupCost = (float) ($order->pickup_cost ?? 0);
            if ($pickupCost > 0) {
                $q['lines'][] = ['label' => 'Eerder ophalen (binnen 2 weken)', 'qty' => 1, 'unit' => $pickupCost, 'subtotal' => $pickupCost];
            }
            $q['subtotal'] = round(array_sum(array_column($q['lines'], 'subtotal')), 2);
            $q['vat']      = round($q['subtotal'] * 0.21, 2);
            $q['total']    = round($q['subtotal'] + $q['vat'], 2);
            return $q;
        };

        $quote = $buildQuote($order->box_count, $order->container_count, $order->media_items ?? []);

        // Accepted custom quote: show the agreed itemised lines instead of a box recompute.
        if (! empty($order->quote_lines)) {
            $qlines = collect($order->quote_lines)->map(fn ($l) => [
                'label'    => $l['label'] ?? '',
                'qty'      => $l['qty'] ?? 1,
                'unit'     => $l['unit'] ?? 0,
                'subtotal' => $l['subtotal'] ?? 0,
            ])->all();
            $qsub  = (float) ($order->quoted_amount_excl_btw ?? array_sum(array_column($qlines, 'subtotal')));
            $quote = [
                'lines'    => $qlines,
                'subtotal' => $qsub,
                'vat'      => round($qsub * 0.21, 2),
                'total'    => round($qsub * 1.21, 2),
            ];
        }
        $hasSignedBon = $order->bons->whereNotNull('picked_up_at')->isNotEmpty();

        // If a bon exists with actuals that differ from the order, also compute the actual quote.
        $actualQuote = null;
        $bonWithActuals = $order->bons->first(fn ($b) =>
            $b->actual_boxes !== null || $b->actual_containers !== null || !empty($b->actual_media)
        );
        if ($bonWithActuals) {
            $boxes = $bonWithActuals->actual_boxes ?? $order->box_count;
            $cntrs = $bonWithActuals->actual_containers ?? $order->container_count;
            $media = !empty($bonWithActuals->actual_media) ? $bonWithActuals->actual_media : ($order->media_items ?? []);

            $mediaKeys = ['hdd', 'ssd', 'usb', 'phone', 'laptop'];
            $orderedMediaInt = array_map('intval', array_merge(array_fill_keys($mediaKeys, 0), (array) ($order->media_items ?? [])));
            $actualMediaInt  = array_map('intval', array_merge(array_fill_keys($mediaKeys, 0), (array) $media));
            ksort($orderedMediaInt); ksort($actualMediaInt);

            if ((int) $boxes !== (int) $order->box_count
                || (int) $cntrs !== (int) $order->container_count
                || $orderedMediaInt !== $actualMediaInt) {
                $actualQuote = $buildQuote($boxes, $cntrs, $media);
            }
        }

        return view('orders.show', compact('order', 'availableTransitions', 'quote', 'hasSignedBon', 'drivers', 'actualQuote'));
    }

    public function confirmPickup(Request $request, Order $order)
    {
        $data = $request->validate([
            'driver_id'        => 'required|exists:drivers,id',
            'pickup_date'      => 'required|date|after_or_equal:today',
            'pickup_window'    => ['required', 'regex:/^(flexibel|ochtend|middag|avond|([01]\d|2[0-3]):00-([01]\d|2[0-3]):00)$/'],
            'duration_minutes' => 'nullable|integer|min:5|max:480',
            'pickup_note'      => 'nullable|string|max:2000',
        ]);

        $driver = Driver::findOrFail($data['driver_id']);
        $bon    = $order->bons()->orderBy('id')->first();

        if (!$bon) {
            $bon = Bon::create([
                'bon_number' => Bon::generateBonNumber(),
                'order_id'   => $order->id,
                'mode'       => $order->delivery_mode,
            ]);
        }

        $bonPatch = [
            'driver_id'            => $driver->id,
            'driver_name_snapshot' => $driver->name,
            'driver_license_last4' => $driver->license_last4,
        ];
        if ($driver->signature_path && empty($bon->driver_signature_path)) {
            $copy = "signatures/bon-{$bon->id}-driver.png";
            Storage::disk('local')->put($copy, Storage::disk('local')->get($driver->signature_path));
            $bonPatch['driver_signature_path'] = $copy;
        }
        $bon->update($bonPatch);

        $order->update([
            'pickup_date'      => $data['pickup_date'],
            'pickup_window'    => $data['pickup_window'],
            'pickup_note'      => $data['pickup_note'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? $order->duration_minutes,
            'state'            => Order::STATE_BEVESTIGD,
            'public_token'     => $order->public_token ?: Str::random(40),
            // Any pending reschedule request is resolved by a new confirmation.
            'reschedule_requested_at'     => null,
            'reschedule_requested_date'   => null,
            'reschedule_requested_window' => null,
            'reschedule_notes'            => null,
        ]);

        try {
            Mail::to($order->customer_email)
                ->send(new PickupConfirmed($order->fresh()->load('customer'), $request->user()));
            return back()->with('status', "Ophaling gepland en bevestigingsmail verstuurd naar {$order->customer_email}.");
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['mail' => 'Planning opgeslagen maar mail kon niet worden verstuurd: ' . $e->getMessage()]);
        }
    }

    public function transition(Request $request, Order $order)
    {
        $to = $request->string('to');
        abort_unless(in_array($to, $this->nextStates($order->state)), 422, 'Invalid transition');
        $order->update(['state' => $to]);
        return back();
    }

    public function sendQuote(Request $request, Order $order)
    {
        abort_unless($order->type === Order::TYPE_QUOTE, 422, 'Only quote-type orders can have a quote sent.');

        // Two distinct intents share this endpoint:
        //   offer   -> a binding quote with an amount + accept button
        //   message -> just extra info / a question from our side, no amount, no accept
        // Explicit intent (not "is the amount empty?") so a forgotten amount can't
        // silently downgrade a real offer to a message.
        $data = $request->validate([
            'intent'                 => 'required|in:offer,message',
            'quote_body'             => 'required|string|max:10000',
            'quote_valid_until'      => 'nullable|date|after:today',
            'lines'                  => 'nullable|array',
            'lines.*.label'          => 'nullable|string|max:255',
            'lines.*.qty'            => 'nullable|numeric|min:0|max:99999',
            'lines.*.unit'           => 'nullable|numeric|min:0|max:999999.99',
            'lines.*.optional'       => 'nullable|boolean',
            'lines.*.editable'       => 'nullable|boolean',
        ]);

        $isOffer = $data['intent'] === 'offer';

        // Build itemised rows: keep only rows that carry a label. subtotal = qty * unit.
        // Optional rows are add-ons the customer can tick on the quote page, so they
        // are excluded from the base amount. Same shape as invoices.lines (+ optional).
        $lines = collect($data['lines'] ?? [])
            ->map(fn ($r) => [
                'label'    => trim($r['label'] ?? ''),
                'qty'      => (float) ($r['qty'] ?? 0),
                'unit'     => (float) ($r['unit'] ?? 0),
                'subtotal' => round((float) ($r['qty'] ?? 0) * (float) ($r['unit'] ?? 0), 2),
                'optional' => ! empty($r['optional']),
                'editable' => ! empty($r['editable']),
            ])
            ->filter(fn ($r) => $r['label'] !== '')
            ->values()
            ->all();

        // Base amount = mandatory (non-optional) rows only; optionals are added later
        // when the customer selects them on the quote page.
        $baseAmount = round(array_sum(array_map(
            fn ($r) => $r['optional'] ? 0 : $r['subtotal'], $lines
        )), 2);
        $anyLineValue = round(array_sum(array_column($lines, 'subtotal')), 2);

        // An offer is built entirely from rows: it needs at least one priced row.
        if ($isOffer && $anyLineValue <= 0) {
            return back()->withInput()->withErrors([
                'lines' => 'Een offerte heeft minimaal één offerteregel met een bedrag nodig.',
            ]);
        }

        $order->update([
            'quoted_amount_excl_btw' => $isOffer ? $baseAmount : null,
            'quote_body'             => $data['quote_body'],
            'quote_lines'            => $isOffer && ! empty($lines) ? $lines : null,
            'quote_valid_until'      => $isOffer
                ? ($data['quote_valid_until'] ?? now()->addDays(14)->toDateString())
                : null,
            'quote_token'            => $order->quote_token ?? Str::random(64),
            'quote_sent_at'          => now(),
        ]);

        try {
            Mail::to($order->customer_email)->send(new QuoteSent($order));
            $noun = $isOffer ? 'Offerte' : 'Bericht';
            return back()->with('status', "{$noun} verzonden naar {$order->customer_email}.");
        } catch (\Throwable $e) {
            report($e);
            $noun = $isOffer ? 'offerte' : 'bericht';
            return back()->withErrors(['mail' => "Kon {$noun} niet versturen: " . $e->getMessage()]);
        }
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
            Order::STATE_NIEUW       => [],  // use Plan ophaling form instead
            Order::STATE_BEVESTIGD   => [Order::STATE_OPGEHAALD],
            Order::STATE_OPGEHAALD   => [Order::STATE_VERNIETIGD],
            Order::STATE_VERNIETIGD  => [Order::STATE_AFGESLOTEN],
            Order::STATE_AFGESLOTEN  => [],
            default                  => [],
        };
    }
}
