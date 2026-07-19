<?php

namespace App\Http\Controllers;

use App\Mail\PickupConfirmed;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PlanningController extends Controller
{
    private const WINDOW_HOURS = [
        'ochtend' => ['08:00', '12:00'],
        'middag'  => ['12:00', '17:00'],
        'avond'   => ['17:00', '20:00'],
    ];

    // Color palette — indexed by driver.id % count. Add colors if you hire > 7 drivers.
    private const DRIVER_PALETTE = [
        ['#F5C518', '#FEF3C7'],
        ['#3B82F6', '#DBEAFE'],
        ['#10B981', '#D1FAE5'],
        ['#EF4444', '#FEE2E2'],
        ['#8B5CF6', '#EDE9FE'],
        ['#EC4899', '#FCE7F3'],
        ['#06B6D4', '#CFFAFE'],
    ];

    public function index()
    {
        $drivers = Driver::active()->orderBy('name')->get(['id', 'name']);
        $calendars = $this->buildCalendars($drivers);
        return view('planning.index', compact('drivers', 'calendars'));
    }

    /**
     * Dagenlijst in plaats van het kalenderbord. Voor de rijdag zelf is een lijst
     * per dag praktischer dan een agenda: je wilt weten wat er vandaag staat, in
     * welke volgorde, met adres erbij. Het bord blijft voor het schuiven en
     * verdelen over chauffeurs.
     */
    public function daily(Request $request)
    {
        $from = $request->filled('from')
            ? \Carbon\Carbon::parse($request->query('from'))->startOfDay()
            : now()->startOfDay();

        $days = min(max((int) $request->query('days', 14), 1), 60);
        $until = $from->copy()->addDays($days - 1)->endOfDay();

        // Twee bronnen. Een losse order draagt zijn eigen datum; een abonnement
        // is één order met meerdere ritten, en die ritten zijn bons met een
        // eigen datum. Beide worden hier tot dezelfde regelvorm gemaakt, zodat
        // de lijst niet hoeft te weten waar een rit vandaan komt.
        $losse = Order::where('state', Order::STATE_BEVESTIGD)
            ->where('type', '!=', Order::TYPE_ABONNEMENT)
            ->whereNotNull('pickup_date')
            ->whereBetween('pickup_date', [$from->toDateString(), $until->toDateString()])
            ->with(['customer', 'bons.driver'])
            ->get()
            ->map(fn (Order $o) => [
                'datum'      => $o->pickup_date,
                'soort'      => 'ophalen',
                'window'     => $o->pickup_window ?: 'flexibel',
                'klant'      => $o->customer_name,
                'bedrijf'    => $o->customer?->company,
                'adres'      => trim(($o->customer_address ?? '').', '.($o->customer_postcode ?? '').' '.($o->customer_city ?? ''), ', '),
                'chauffeur'  => $o->bons->first()?->driver_name_snapshot,
                'ref'        => $o->order_number,
                'url'        => route('orders.show', $o),
                'abonnement' => null,
            ]);

        $ritten = \App\Models\Bon::whereNotNull('planned_for')
            ->whereBetween('planned_for', [$from->toDateString(), $until->toDateString()])
            ->whereHas('order', fn ($q) => $q->where('type', Order::TYPE_ABONNEMENT))
            ->with(['order.customer'])
            ->get()
            ->map(fn ($b) => [
                'datum'      => $b->planned_for,
                'soort'      => match ($b->mode) {
                    \App\Models\Bon::MODE_BEZORGING => 'brengen',
                    \App\Models\Bon::MODE_RETOUR    => 'retour',
                    default                          => 'ophalen',
                },
                'window'     => $b->planned_window ?: 'flexibel',
                'klant'      => $b->order->customer_name,
                'bedrijf'    => $b->order->customer?->company,
                'adres'      => trim(($b->order->customer_address ?? '').', '.($b->order->customer_postcode ?? '').' '.($b->order->customer_city ?? ''), ', '),
                'chauffeur'  => $b->driver_name_snapshot,
                'ref'        => $b->bon_number,
                'url'        => route('bons.show', $b),
                'abonnement' => ['nr' => $b->order->order_number, 'url' => route('abonnementen.show', $b->order)],
            ]);

        $orders = $losse->concat($ritten)
            ->sortBy(fn ($r) => [$r['datum']->toDateString(), $r['window'], $r['klant']])
            ->groupBy(fn ($r) => $r['datum']->toDateString());

        return view('planning.daily', compact('orders', 'from', 'until', 'days'));
    }

    public function events(Request $request): JsonResponse
    {
        $start = $request->query('start');
        $end   = $request->query('end');

        $orders = Order::where('state', Order::STATE_BEVESTIGD)
            ->where(function ($q) use ($start, $end) {
                if ($start && $end) {
                    $q->whereBetween('pickup_date', [$start, $end])
                      ->orWhereBetween('reschedule_requested_date', [$start, $end]);
                }
            })
            ->with('customer', 'bons.driver')
            ->get();

        $events = [];
        foreach ($orders as $order) {
            $driverId = $order->bons->first()?->driver_id;
            if ($order->pickup_date) {
                $events[] = $this->buildEvent($order, 'confirmed', $driverId);
            }
            if ($order->reschedule_requested_at && $order->reschedule_requested_date) {
                $events[] = $this->buildEvent($order, 'proposal', $driverId);
            }
        }

        // Abonnementsritten zijn bons, geen orders, en stonden daarom niet op het
        // bord. Ze horen er wel: het bord is waar je over chauffeurs verdeelt.
        if ($start && $end) {
            $bons = \App\Models\Bon::whereNotNull('planned_for')
                ->whereBetween('planned_for', [$start, $end])
                ->whereHas('order', fn ($q) => $q->where('type', Order::TYPE_ABONNEMENT))
                ->with('order')
                ->get();
            foreach ($bons as $bon) {
                $events[] = $this->buildBonEvent($bon);
            }
        }

        return response()->json($events);
    }

    public function move(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind'        => 'required|in:order,bon',
            'id'          => 'required|integer',
            'pickup_date' => 'required|date|after_or_equal:today',
            'window'      => ['required', 'regex:/^(flexibel|ochtend|middag|avond|([01]\d|2[0-3]):00-([01]\d|2[0-3]):00)$/'],
        ]);

        // Een abonnementsrit is een bon: verslepen werkt de bon bij, niet een
        // order. De klant krijgt geen bevestigingsmail (die belooft "ophaal"),
        // wel de dag ervoor een herinnering die de nieuwe datum meeneemt.
        if ($data['kind'] === 'bon') {
            $bon = \App\Models\Bon::findOrFail($data['id']);
            abort_if($bon->picked_up_at !== null, 422, 'Deze rit is al gereden en kan niet meer verplaatst worden.');

            // Een verplaatste rit is niet meer de ritmedatum, dus reset de
            // herinnering zodat hij opnieuw uitgaat voor de nieuwe dag.
            $bon->update([
                'planned_for'      => $data['pickup_date'],
                'planned_window'   => $data['window'],
                'reminder_sent_at' => null,
            ]);

            return response()->json(['ok' => true, 'mailed' => false]);
        }

        $order = Order::findOrFail($data['id']);
        abort_unless($order->state === Order::STATE_BEVESTIGD, 422, 'Alleen bevestigde orders kunnen verplaatst worden.');

        $changed = $order->pickup_date?->toDateString() !== $data['pickup_date']
                || $order->pickup_window !== $data['window'];

        $order->update([
            'pickup_date'                 => $data['pickup_date'],
            'pickup_window'               => $data['window'],
            'reschedule_requested_at'     => null,
            'reschedule_requested_date'   => null,
            'reschedule_requested_window' => null,
            'reschedule_notes'            => null,
        ]);

        $mailed = false;
        if ($changed) {
            try {
                Mail::to($order->customer_email)
                    ->send(new PickupConfirmed($order->fresh()->load('customer'), $request->user()));
                $mailed = true;
            } catch (\Throwable $e) {
                report($e);
                return response()->json(['ok' => true, 'mailed' => false, 'error' => 'Verplaatst, maar mail kon niet worden verstuurd: ' . $e->getMessage()]);
            }
        }

        return response()->json(['ok' => true, 'mailed' => $mailed]);
    }

    /**
     * Wijs een chauffeur toe vanaf het planbord, zonder de rit open te hoeven
     * klikken. Werkt op een bon (abonnementsrit) of op de bon van een losse
     * order. Alleen de chauffeur, geen datum of mail: verslepen doet de datum.
     */
    public function assignDriver(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind'      => 'required|in:order,bon',
            'id'        => 'required|integer',
            'driver_id' => 'nullable|integer|exists:drivers,id',
        ]);

        if ($data['kind'] === 'bon') {
            $bon = \App\Models\Bon::findOrFail($data['id']);
            abort_if($bon->picked_up_at !== null, 422, 'Deze rit is al gereden.');
        } else {
            $order = Order::findOrFail($data['id']);
            $bon = $order->bons()->orderBy('id')->first();
            abort_unless($bon, 422, 'Plan eerst de ophaling voor je een chauffeur toewijst.');
        }

        $this->applyDriver($bon, $data['driver_id'] ? (int) $data['driver_id'] : null);

        $driver = $bon->fresh()->driver;
        return response()->json([
            'ok'         => true,
            'driverId'   => $driver?->id,
            'driverName' => $driver?->name,
            'calendarId' => $driver ? 'driver-' . $driver->id : 'unassigned',
        ]);
    }

    /** Zet (of wist) de chauffeur op een bon, met dezelfde snapshot als confirmPickup. */
    private function applyDriver(\App\Models\Bon $bon, ?int $driverId): void
    {
        if ($driverId === null) {
            $bon->update(['driver_id' => null, 'driver_name_snapshot' => null, 'driver_license_last4' => null]);
            return;
        }

        $driver = Driver::findOrFail($driverId);
        $patch = [
            'driver_id'            => $driver->id,
            'driver_name_snapshot' => $driver->name,
            'driver_license_last4' => $driver->license_last4,
        ];
        // Handtekening uit het profiel voorvullen als de bon er nog geen heeft.
        if ($driver->signature_path && empty($bon->driver_signature_path)) {
            $copy = "signatures/bon-{$bon->id}-driver.png";
            \Illuminate\Support\Facades\Storage::disk('local')->put(
                $copy,
                \Illuminate\Support\Facades\Storage::disk('local')->get($driver->signature_path)
            );
            $patch['driver_signature_path'] = $copy;
        }
        $bon->update($patch);
    }

    private function buildCalendars($drivers): array
    {
        $calendars = [];
        foreach ($drivers as $driver) {
            [$main, $container] = self::DRIVER_PALETTE[$driver->id % count(self::DRIVER_PALETTE)];
            $calendars['driver-' . $driver->id] = [
                'colorName'   => 'driver-' . $driver->id,
                'label'       => $driver->name,
                'lightColors' => ['main' => $main, 'container' => $container, 'onContainer' => '#0A0A0A'],
            ];
        }
        $calendars['unassigned'] = [
            'colorName'   => 'unassigned',
            'label'       => 'Geen chauffeur',
            'lightColors' => ['main' => '#64748B', 'container' => '#E2E8F0', 'onContainer' => '#0A0A0A'],
        ];
        $calendars['proposal'] = [
            'colorName'   => 'proposal',
            'label'       => 'Klantvoorstel',
            'lightColors' => ['main' => '#E67E22', 'container' => '#FED7AA', 'onContainer' => '#8B4513'],
        ];
        return $calendars;
    }

    private function buildEvent(Order $order, string $type, ?int $driverId): array
    {
        $isProposal = $type === 'proposal';
        $date       = $isProposal ? $order->reschedule_requested_date : $order->pickup_date;
        $window     = $isProposal ? $order->reschedule_requested_window : ($order->pickup_window ?? 'flexibel');

        $title = ($isProposal ? '⚠ VOORSTEL: ' : '')
               . $order->order_number . ' · ' . $order->customer_name;

        return $this->formatEvent(
            id: ($isProposal ? 'proposal-' : 'order-') . $order->id,
            title: $title,
            calendarId: $isProposal ? 'proposal' : ($driverId ? 'driver-' . $driverId : 'unassigned'),
            date: $date,
            window: $window,
            duration: (int) ($order->duration_minutes ?? 30),
            ext: [
                '_kind'     => 'order',
                '_moveId'   => $order->id,
                '_type'     => $type,
                '_window'   => $window,
                '_driverId' => $driverId,
                '_orderUrl' => route('orders.show', $order),
                '_customer' => $order->customer_name,
                '_address'  => trim(($order->customer_postcode ?? '') . ' ' . ($order->customer_city ?? '')),
            ],
        );
    }

    /**
     * Een abonnementsrit op het bord. De rit is een bon, niet een order, dus de
     * datum komt van de bon en verslepen werkt de bon bij. Brengen en retour
     * krijgen een prefix, zodat een chauffeur niet met een lege container naar
     * een ophaling rijdt of andersom.
     */
    private function buildBonEvent(\App\Models\Bon $bon): array
    {
        $prefix = match ($bon->mode) {
            \App\Models\Bon::MODE_BEZORGING => 'BRENG · ',
            \App\Models\Bon::MODE_RETOUR    => 'RETOUR · ',
            default                          => '',
        };
        $order  = $bon->order;
        $window = $bon->planned_window ?: 'flexibel';

        return $this->formatEvent(
            id: 'bon-' . $bon->id,
            title: $prefix . $bon->bon_number . ' · ' . $order->customer_name,
            calendarId: $bon->driver_id ? 'driver-' . $bon->driver_id : 'unassigned',
            date: $bon->planned_for,
            window: $window,
            duration: 30,
            ext: [
                '_kind'     => 'bon',
                '_moveId'   => $bon->id,
                // Alleen nog niet gereden ritten mogen versleept worden.
                '_type'     => $bon->picked_up_at ? 'done' : 'confirmed',
                '_window'   => $window,
                '_driverId' => $bon->driver_id,
                '_orderUrl' => route('bons.show', $bon),
                '_customer' => $order->customer_name,
                '_address'  => trim(($order->customer_postcode ?? '') . ' ' . ($order->customer_city ?? '')),
            ],
        );
    }

    private function formatEvent(string $id, string $title, string $calendarId, $date, string $window, int $duration, array $ext): array
    {
        $event = ['id' => $id, 'title' => $title, 'calendarId' => $calendarId] + $ext;

        if ($window === 'flexibel' || ! $window) {
            $event['start'] = $date->toDateString();
            $event['end']   = $date->toDateString();
        } else {
            // Named day-parts come from WINDOW_HOURS; specific hourly blocks
            // arrive as "HH:00-HH:00" and are split into their own bounds.
            if (isset(self::WINDOW_HOURS[$window])) {
                [$from, $to] = self::WINDOW_HOURS[$window];
            } else {
                [$from, $to] = explode('-', $window, 2);
            }
            $startTs = strtotime($date->toDateString() . ' ' . $from);
            $endTs   = min(strtotime($date->toDateString() . ' ' . $to), $startTs + $duration * 60);
            $event['start'] = date('Y-m-d H:i', $startTs);
            $event['end']   = date('Y-m-d H:i', $endTs);
        }

        return $event;
    }
}
