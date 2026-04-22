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

        return response()->json($events);
    }

    public function move(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id'    => 'required|integer|exists:orders,id',
            'pickup_date' => 'required|date|after_or_equal:today',
            'window'      => 'required|in:ochtend,middag,avond,flexibel',
        ]);

        $order = Order::findOrFail($data['order_id']);
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

        $event = [
            'id'          => ($isProposal ? 'proposal-' : 'order-') . $order->id,
            'title'       => $title,
            'calendarId'  => $isProposal
                ? 'proposal'
                : ($driverId ? 'driver-' . $driverId : 'unassigned'),
            // extended props for JS callbacks
            '_orderId'    => $order->id,
            '_type'       => $type,
            '_window'     => $window,
            '_driverId'   => $driverId,
            '_orderUrl'   => route('orders.show', $order),
            '_customer'   => $order->customer_name,
            '_address'    => trim(($order->customer_postcode ?? '') . ' ' . ($order->customer_city ?? '')),
        ];

        if ($window === 'flexibel' || !$window) {
            $event['start'] = $date->toDateString();
            $event['end']   = $date->toDateString();
        } else {
            [$from, $to]    = self::WINDOW_HOURS[$window];
            $event['start'] = $date->toDateString() . ' ' . $from;
            $event['end']   = $date->toDateString() . ' ' . $to;
        }

        return $event;
    }
}
