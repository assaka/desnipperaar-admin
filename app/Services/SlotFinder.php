<?php

namespace App\Services;

use App\Models\Bon;
use App\Models\Order;
use App\Support\Geocoder;
use App\Support\WorkingDays;
use Carbon\Carbon;

/**
 * Welke ophaalmomenten kunnen wij deze klant aanbieden, en wat kost zo'n moment.
 *
 * Geen routeoptimalisatie. Wij rijden een handvol stops per dag, en dan zijn er
 * maar twee vragen die ertoe doen:
 *
 *   1. Past de rit nog in dat dagdeel? Optellen van de duur van de stops die er
 *      al staan, plus de reistijd, tegen de capaciteit uit de config.
 *   2. Rijden wij die dag toch al langs? De afstand tot de dichtstbijzijnde
 *      geplande stop van die dag. Ligt de klant daarbinnen, dan kost de rit ons
 *      een omweg en geen hele rit, en dan is ophalen gratis.
 *
 * Die tweede vraag maakt de belofte op /order waar. Daar staat dat ophalen
 * buiten regio Amsterdam gratis is vanaf twee weken omdat wij het combineren met
 * een rit in de buurt. Tot nu toe was dat handwerk. Hier wordt het een getal:
 * een moment is gratis als er die dag al iemand in de buurt staat.
 *
 * Afstanden zijn hemelsbreed maal een vaste factor. Voor "in de buurt of niet"
 * is dat nauwkeurig genoeg en het kost geen API-quotum. De echte wegafstand voor
 * de prijs komt van /api/distance op de publieke site en staat als pickup_km op
 * de order; die gebruiken wij zodra hij er is.
 */
class SlotFinder
{
    /**
     * Hoeveel geplande stops wij per zoekopdracht alsnog mogen geocoderen. Oude
     * orders hebben nog geen coördinaat, en die halen wij hier op zodra ze in
     * beeld komen. De grens houdt de eerste zoekopdracht na het uitrollen snel;
     * wat overblijft komt de volgende keer aan de beurt, want elk gevonden punt
     * wordt op de order bewaard.
     */
    private const GEOCODE_BUDGET = 20;

    public const WINDOW_LABELS = [
        'ochtend'  => 'Ochtend (08:00 – 12:00)',
        'middag'   => 'Middag (12:00 – 17:00)',
        'avond'    => 'Avond (17:00 – 20:00)',
        'flexibel' => 'Flexibel',
    ];

    private int $geocodeBudget = self::GEOCODE_BUDGET;

    /**
     * Alle momenten binnen de horizon, beschikbaar of niet.
     *
     * De niet-beschikbare gaan mee omdat de admin wil zien waaróm een dag vol
     * zit. De klantpagina filtert ze er weer uit, zie offer().
     *
     * @return array{
     *     point: array{lat: float, lon: float}|null,
     *     depot_km: float|null,
     *     duration_minutes: int,
     *     from: string,
     *     until: string,
     *     slots: array<int, array<string, mixed>>
     * }
     */
    public function forOrder(Order $order, ?int $durationMinutes = null): array
    {
        $duration = $durationMinutes
            ?? $order->duration_minutes
            ?? (int) config('desnipperaar.planning.default_duration');

        $point = Geocoder::forOrder($order);

        // De wegafstand die de klant op /order te zien kreeg is de eerlijkste
        // bron voor de prijs: daar is de ophaalprijs mee berekend en die staat
        // in zijn bevestiging. Alleen als hij ontbreekt schatten wij zelf.
        $depotKm = $order->pickup_km !== null
            ? (float) $order->pickup_km
            : ($point ? Geocoder::roadKm(Geocoder::depot(), $point) : null);

        $from  = $this->firstOfferableDate();
        $until = $from->copy()->addDays((int) config('desnipperaar.planning.horizon_days') - 1);

        $stopsByDate = $this->plannedStops($from, $until, $order->id);

        $slots = [];
        for ($day = $from->copy(); $day->lte($until); $day->addDay()) {
            foreach ($this->slotsForDay($day, $stopsByDate, $point, $depotKm, $duration) as $slot) {
                $slots[] = $slot;
            }
        }

        return [
            'point'            => $point,
            'depot_km'         => $depotKm === null ? null : round($depotKm, 1),
            'duration_minutes' => $duration,
            'from'             => $from->toDateString(),
            'until'            => $until->toDateString(),
            'slots'            => $slots,
        ];
    }

    /**
     * De momenten die wij een klant voorleggen: alleen wat echt kan, goedkoop
     * eerst en daarbinnen zo vroeg mogelijk, en niet meer dan een mens uit een
     * lijst kiest. De uitkomst staat weer op datum, want zo leest een agenda.
     *
     * $maxPrice is wat de klant bij het bestellen al heeft geaccepteerd. Duurdere
     * momenten laten wij weg in plaats van ze te tonen, want de prijs stond in
     * zijn orderbevestiging en die is ook de factuur. Achteraf meer rekenen omdat
     * hij een andere dag koos hoort niet, en stilzwijgend het verschil zelf
     * betalen ook niet. Voor een klant buiten regio Amsterdam die koos voor
     * gratis ophalen betekent dit precies wat op /order staat: hij krijgt de
     * dagen waarop wij toch al in de buurt zijn. Wil hij een andere dag, dan is
     * dat een gesprek en geen knop.
     *
     * @param  array<int, array<string, mixed>>  $slots
     * @return array<int, array<string, mixed>>
     */
    public function offer(array $slots, ?int $max = null, ?float $maxPrice = null): array
    {
        $max ??= (int) config('desnipperaar.planning.max_offered');

        $available = array_values(array_filter(
            $slots,
            fn (array $s) => $s['available']
                && ($maxPrice === null || ($s['price'] ?? 0) <= $maxPrice + 0.001)
        ));

        usort($available, function (array $a, array $b) {
            return [$a['price'] ?? 0, $a['date'], $a['window']]
               <=> [$b['price'] ?? 0, $b['date'], $b['window']];
        });

        $picked = array_slice($available, 0, max(1, $max));

        usort($picked, fn (array $a, array $b) => [$a['date'], $a['window']] <=> [$b['date'], $b['window']]);

        return $picked;
    }

    /**
     * Is dit precies een van de momenten die wij aanbieden?
     *
     * De klantpagina roept dit vlak voor het opslaan nog eens aan. Tussen het
     * tonen van de lijst en het klikken kan er een andere ophaling op die dag
     * zijn gepland, en dan is het moment weg. Wij houden geen reservering aan:
     * bij dit aantal ritten per dag is opnieuw narekenen eenvoudiger dan een
     * tabel met vervallende claims, en het antwoord is altijd actueel.
     *
     * @param  array<int, array<string, mixed>>  $slots
     */
    public function isOffered(array $slots, string $date, string $window, ?float $maxPrice = null): bool
    {
        foreach ($this->offer($slots, null, $maxPrice) as $slot) {
            if ($slot['date'] === $date && $slot['window'] === $window) {
                return true;
            }
        }

        return false;
    }

    /**
     * De vroegste dag die wij mogen aanbieden. Morgen kan niet: de dag ervoor
     * gaat de herinnering uit en wordt de bus geladen.
     */
    public function firstOfferableDate(): Carbon
    {
        return now()->startOfDay()->addDays((int) config('desnipperaar.planning.lead_days'));
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $stopsByDate
     * @return array<int, array<string, mixed>>
     */
    private function slotsForDay(Carbon $day, array $stopsByDate, ?array $point, ?float $depotKm, int $duration): array
    {
        $windows = config('desnipperaar.planning.week')[$day->dayOfWeekIso] ?? [];
        if (! $windows || ! WorkingDays::isWorkingDay($day)) {
            return [];
        }

        $stops = $stopsByDate[$day->toDateString()] ?? [];

        // Hoe dicht staat de dichtstbijzijnde geplande stop van deze dag? Over
        // alle dagdelen heen: zijn wij die dag in Haarlem, dan zijn wij die dag
        // in Haarlem, ook als dat 's ochtends was en de klant 's middags kan.
        $nearest = $this->nearestStop($point, $stops);
        $onRoute = $nearest !== null
            && $nearest['km'] <= (float) config('desnipperaar.planning.cluster_km');

        $needed = $duration + (int) config(
            $onRoute ? 'desnipperaar.planning.travel_minutes_nearby' : 'desnipperaar.planning.travel_minutes'
        );

        // Een flexibele stop zit nergens vast en belast daarom de dag als
        // geheel, niet een dagdeel. Zo blokkeert hij geen ochtend die hij
        // misschien niet eens gebruikt, en telt hij wel mee als de dag vol raakt.
        $dayCapacity = 0;
        foreach ($windows as $w) {
            $dayCapacity += (int) config("desnipperaar.planning.capacity_minutes.{$w}", 0);
        }
        $dayUsed = array_sum(array_column($stops, 'minutes'));

        [$price, $priceReason] = $this->price($depotKm, $onRoute);

        $slots = [];
        foreach ($windows as $window) {
            $capacity = (int) config("desnipperaar.planning.capacity_minutes.{$window}", 0);
            $used = array_sum(array_column(
                array_filter($stops, fn (array $s) => $s['window'] === $window),
                'minutes'
            ));

            $windowFree = max(0, $capacity - $used);
            $dayFree    = max(0, $dayCapacity - $dayUsed);

            $reason = null;
            if ($windowFree < $needed) {
                $reason = 'dagdeel vol';
            } elseif ($dayFree < $needed) {
                $reason = 'dag vol';
            }

            $slots[] = [
                'date'             => $day->toDateString(),
                'weekday'          => $day->locale('nl')->translatedFormat('l'),
                'window'           => $window,
                'window_label'     => self::WINDOW_LABELS[$window] ?? $window,
                'available'        => $reason === null,
                'reason'           => $reason,
                'capacity_minutes' => $capacity,
                'used_minutes'     => $used,
                'free_minutes'     => $windowFree,
                'needed_minutes'   => $needed,
                'day_free_minutes' => $dayFree,
                'stops'            => count(array_filter($stops, fn (array $s) => $s['window'] === $window)),
                'day_stops'        => count($stops),
                'on_route'         => $onRoute,
                'nearest_km'       => $nearest === null ? null : round($nearest['km'], 1),
                'nearest_label'    => $nearest['label'] ?? null,
                'price'            => $price,
                'price_reason'     => $priceReason,
            ];
        }

        return $slots;
    }

    /**
     * Wat een ophaling op dit moment kost.
     *
     * Regio Amsterdam is altijd gratis. Daarbuiten is het gratis zodra wij die
     * dag toch al in de buurt zijn; is dat niet zo, dan is het een rit die wij
     * speciaal maken en geldt het tarief per kilometer boven de eerste 20, net
     * als op /order. Zonder coördinaat zeggen wij niets: dan beslist de admin.
     *
     * @return array{0: float|null, 1: string|null}
     */
    private function price(?float $depotKm, bool $onRoute): array
    {
        if ($depotKm === null) {
            return [null, null];
        }

        $freeRadius = (float) config('desnipperaar.planning.free_radius_km');

        if ($depotKm <= $freeRadius) {
            return [0.0, 'regio'];
        }

        if ($onRoute) {
            return [0.0, 'route'];
        }

        $billable = $depotKm - $freeRadius;

        return [round($billable * (float) config('desnipperaar.planning.per_km'), 2), 'losse rit'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $stops
     * @return array{km: float, label: string}|null
     */
    private function nearestStop(?array $point, array $stops): ?array
    {
        if (! $point) {
            return null;
        }

        $best = null;
        foreach ($stops as $stop) {
            if (! $stop['point']) {
                continue;
            }
            $km = Geocoder::roadKm($point, $stop['point']);
            if ($best === null || $km < $best['km']) {
                $best = ['km' => $km, 'label' => $stop['label']];
            }
        }

        return $best;
    }

    /**
     * Alles wat er in deze periode al staat, als één soort regel per dag.
     *
     * Twee bronnen, net als het planbord: een losse order draagt zijn eigen
     * datum, een abonnementsrit is een bon met een eigen datum. De order die wij
     * aan het plannen zijn telt niet mee tegen zichzelf.
     *
     * @return array<string, array<int, array{window: string, minutes: int, point: array|null, label: string}>>
     */
    private function plannedStops(Carbon $from, Carbon $until, ?int $excludeOrderId): array
    {
        $defaultDuration = (int) config('desnipperaar.planning.default_duration');
        $byDate = [];

        // Nieuw én bevestigd, niet alleen bevestigd zoals het planbord doet. Een
        // order met een datum die nog niet bevestigd is, is een klant die zijn
        // moment zelf heeft gekozen en waar nog een chauffeur bij moet. Die
        // ruimte is bezet; tellen wij hem niet mee, dan kan een tweede klant
        // hetzelfde dagdeel nog eens boeken.
        $orders = Order::query()
            ->whereIn('state', [Order::STATE_NIEUW, Order::STATE_BEVESTIGD])
            ->where('type', '!=', Order::TYPE_ABONNEMENT)
            ->whereNotNull('pickup_date')
            ->whereBetween('pickup_date', [$from->toDateString(), $until->toDateString()])
            ->when($excludeOrderId, fn ($q) => $q->where('id', '!=', $excludeOrderId))
            ->get(['id', 'order_number', 'customer_city', 'customer_postcode', 'lat', 'lon', 'geocoded_at', 'pickup_date', 'pickup_window', 'duration_minutes']);

        foreach ($orders as $planned) {
            $byDate[$planned->pickup_date->toDateString()][] = [
                'window'  => $planned->pickup_window ?: 'flexibel',
                'minutes' => (int) ($planned->duration_minutes ?? $defaultDuration),
                'point'   => $this->pointFor($planned),
                'label'   => trim($planned->order_number.' '.($planned->customer_city ?? '')),
            ];
        }

        $bons = Bon::query()
            ->whereNotNull('planned_for')
            ->whereBetween('planned_for', [$from->toDateString(), $until->toDateString()])
            ->whereHas('order', fn ($q) => $q->where('type', Order::TYPE_ABONNEMENT))
            ->with(['order' => fn ($q) => $q->select('id', 'order_number', 'customer_city', 'customer_postcode', 'lat', 'lon', 'geocoded_at')])
            ->get(['id', 'bon_number', 'order_id', 'planned_for', 'planned_window']);

        foreach ($bons as $bon) {
            $byDate[$bon->planned_for->toDateString()][] = [
                'window'  => $bon->planned_window ?: 'flexibel',
                'minutes' => $defaultDuration,
                'point'   => $bon->order ? $this->pointFor($bon->order) : null,
                'label'   => trim($bon->bon_number.' '.($bon->order?->customer_city ?? '')),
            ];
        }

        return $byDate;
    }

    /**
     * Het punt van een al geplande stop. Ontbreekt het, dan zoeken wij het op
     * zolang het budget strekt; daarna telt de stop gewoon mee voor de
     * capaciteit en niet voor de nabijheid.
     */
    private function pointFor(Order $order): ?array
    {
        if ($order->lat !== null && $order->lon !== null) {
            return ['lat' => (float) $order->lat, 'lon' => (float) $order->lon];
        }

        if ($order->geocoded_at !== null || $this->geocodeBudget <= 0) {
            return null;
        }

        $this->geocodeBudget--;

        return Geocoder::forOrder($order);
    }
}
