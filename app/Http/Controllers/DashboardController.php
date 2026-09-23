<?php

namespace App\Http\Controllers;

use App\Models\Bon;
use App\Models\Invoice;
use App\Models\Order;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Omzet per maand en de ritten die eraan komen.
     *
     * Omzet is wat er gefactureerd is, excl. btw, op de maand waarin de
     * factuur is aangemaakt. Concepten tellen niet mee (de klant heeft ze niet) en een
     * vervallen factuur ook niet. Een creditfactuur telt wel mee, negatief, in
     * de maand waarin hij is gemaakt: zo staat de tegenboeking waar hij in de
     * boeken staat en wordt een oude maand niet achteraf herschreven.
     *
     * Groeperen gebeurt in PHP en niet in SQL. Het zijn een paar honderd
     * facturen, en zo hoeft de maandindeling niet per database anders.
     */
    public function index()
    {
        $invoices = Invoice::whereNotIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_CANCELED])
            ->get(['id', 'created_at', 'paid_at', 'status', 'credits_invoice_id',
                   'amount_excl_btw', 'vat_amount', 'amount_incl_btw']);

        // Twaalf maanden terug tot en met deze maand, ook als er in een maand
        // niets is gefactureerd: een gat in de reeks is zelf informatie.
        $months = [];
        $cursor = now()->startOfMonth()->subMonths(11);
        for ($i = 0; $i < 12; $i++) {
            $months[$cursor->format('Y-m')] = [
                'month'    => $cursor->copy(),
                'count'    => 0,
                'gross'    => 0.0,
                'credit'   => 0.0,
                'net'      => 0.0,
                'net_incl' => 0.0,
                'received' => 0.0,
                'open'     => 0.0,
            ];
            $cursor->addMonth();
        }

        $blank = fn (Carbon $m) => ['month' => $m, 'count' => 0, 'gross' => 0.0, 'credit' => 0.0,
                                    'net' => 0.0, 'net_incl' => 0.0, 'received' => 0.0, 'open' => 0.0];

        foreach ($invoices as $inv) {
            $key = $inv->created_at->format('Y-m');
            $months[$key] ??= $blank($inv->created_at->copy()->startOfMonth());

            $excl = (float) $inv->amount_excl_btw;
            if ($inv->isCreditNote()) {
                $months[$key]['credit'] += $excl;
            } else {
                $months[$key]['count']++;
                $months[$key]['gross'] += $excl;
                if ($inv->status === Invoice::STATUS_SENT) {
                    $months[$key]['open'] += (float) $inv->amount_incl_btw;
                }
            }
            $months[$key]['net']      += $excl;
            $months[$key]['net_incl'] += (float) $inv->amount_incl_btw;

            // Ontvangen hangt aan de betaaldatum, niet aan de aanmaakdatum: een
            // factuur van eind maart die in april binnenkomt is geld van april.
            if ($inv->status === Invoice::STATUS_PAID && $inv->paid_at && ! $inv->isCreditNote()) {
                $pkey = $inv->paid_at->format('Y-m');
                $months[$pkey] ??= $blank($inv->paid_at->copy()->startOfMonth());
                $months[$pkey]['received'] += (float) $inv->amount_incl_btw;
            }
        }

        krsort($months);

        $thisMonth = now()->format('Y-m');
        $lastMonth = now()->subMonthNoOverflow()->format('Y-m');
        $thisYear  = now()->format('Y');

        $kpi = [
            'this_month' => $months[$thisMonth]['net'] ?? 0.0,
            'last_month' => $months[$lastMonth]['net'] ?? 0.0,
            'this_year'  => collect($months)->filter(fn ($m, $k) => str_starts_with($k, $thisYear))->sum('net'),
            'open'       => $invoices->where('status', Invoice::STATUS_SENT)
                                     ->reject->isCreditNote()
                                     ->sum(fn ($i) => (float) $i->amount_incl_btw),
            'all_time'   => collect($months)->sum('net'),
        ];

        $maxNet = max(1.0, collect($months)->max('net'));

        return view('dashboard.index', [
            'months'   => $months,
            'kpi'      => $kpi,
            'maxNet'   => $maxNet,
            'pickups'  => $this->scheduledPickups(),
            'overdue'  => $this->overduePickups(),
            'unplanned' => Order::where('type', Order::TYPE_DIRECT)
                ->where('state', Order::STATE_NIEUW)
                ->whereNull('pickup_date')
                ->count(),
        ]);
    }

    /**
     * Alle ritten vanaf vandaag, gewone orders en abonnementsritten samen, op
     * datum. Dezelfde bron als het planbord: een order met een datum die nog
     * niet is opgehaald, en een abonnementsbon die nog niet is gereden.
     */
    private function scheduledPickups()
    {
        $today = now()->toDateString();

        $orders = Order::with('customer', 'bons.driver')
            ->whereIn('state', [Order::STATE_NIEUW, Order::STATE_BEVESTIGD])
            ->where('type', '!=', Order::TYPE_ABONNEMENT)
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '>=', $today)
            ->get()
            ->map(fn (Order $o) => $this->orderRow($o));

        $bons = Bon::with('order', 'driver')
            ->whereNotNull('planned_for')
            ->where('planned_for', '>=', $today)
            ->whereNull('picked_up_at')
            ->whereHas('order', fn ($q) => $q->where('type', Order::TYPE_ABONNEMENT)
                                             ->where('state', '!=', Order::STATE_GEANNULEERD))
            ->get()
            ->map(fn (Bon $b) => $this->bonRow($b));

        return $orders->concat($bons)
            ->sortBy(fn ($r) => $r['date']->format('Y-m-d').' '.$r['window'])
            ->values()
            ->groupBy(fn ($r) => $r['date']->format('Y-m-d'));
    }

    /** Een datum die voorbij is terwijl er nog niets is opgehaald. */
    private function overduePickups()
    {
        $today = now()->toDateString();

        $orders = Order::with('customer', 'bons.driver')
            ->whereIn('state', [Order::STATE_NIEUW, Order::STATE_BEVESTIGD])
            ->where('type', '!=', Order::TYPE_ABONNEMENT)
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '<', $today)
            ->get()
            ->map(fn (Order $o) => $this->orderRow($o));

        $bons = Bon::with('order', 'driver')
            ->whereNotNull('planned_for')
            ->where('planned_for', '<', $today)
            ->whereNull('picked_up_at')
            ->whereHas('order', fn ($q) => $q->where('type', Order::TYPE_ABONNEMENT)
                                             ->where('state', '!=', Order::STATE_GEANNULEERD))
            ->get()
            ->map(fn (Bon $b) => $this->bonRow($b));

        return $orders->concat($bons)->sortBy(fn ($r) => $r['date']->format('Y-m-d'))->values();
    }

    private function orderRow(Order $o): array
    {
        $loc    = $o->pickupLocation();
        $driver = $o->bons->first()?->driver;

        return [
            'date'     => $o->pickup_date,
            'window'   => (string) $o->pickup_window,
            'kind'     => 'order',
            'number'   => $o->order_number,
            'url'      => route('orders.show', $o),
            'customer' => $o->customer_name,
            'postcode' => $loc['postcode'] ?? null,
            'city'     => $loc['city'] ?? null,
            'what'     => $this->volume($o),
            'driver'   => $driver?->name,
            'confirmed' => $o->state === Order::STATE_BEVESTIGD,
        ];
    }

    private function bonRow(Bon $b): array
    {
        $o   = $b->order;
        $loc = $o->pickupLocation();

        return [
            'date'     => $b->planned_for,
            'window'   => (string) $b->planned_window,
            'kind'     => $b->mode,
            'number'   => $o->order_number,
            'url'      => route('abonnementen.show', $o),
            'customer' => $o->customer_name,
            'postcode' => $loc['postcode'] ?? null,
            'city'     => $loc['city'] ?? null,
            'what'     => 'Abonnement · '.$b->mode,
            'driver'   => $b->driver?->name ?? $b->driver_name_snapshot,
            'confirmed' => $b->driver_id !== null,
        ];
    }

    private function volume(Order $o): string
    {
        $parts = [];
        if ($o->box_count)       $parts[] = $o->box_count.' doos';
        if ($o->container_count) $parts[] = $o->container_count.' container';
        $media = array_sum(array_map('intval', (array) $o->media_items));
        if ($media)              $parts[] = $media.' datadrager';

        return $parts ? implode(' · ', $parts) : '—';
    }
}
