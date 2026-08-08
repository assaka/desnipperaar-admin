<?php

namespace App\Services;

use App\Models\Bon;
use App\Models\Order;
use App\Support\Geocoder;
use App\Support\WorkingDays;
use Carbon\Carbon;

/**
 * Welke ophaalmomenten kunnen wij deze klant aanbieden.
 *
 * De prijs komt hier niet vandaan. Wat de klant betaalt volgt uit zijn adres en
 * is bij het bestellen al berekend: binnen regio Amsterdam gratis, daarbuiten
 * max(0, km - 20) maal het kilometertarief. Die formule staat op /order en wordt
 * hier alleen overgenomen om te kunnen laten zien wat een rit ons kost.
 *
 * Wat wij hier wél bepalen is welke dagen kunnen, en welke dagen slim zijn:
 *
 *   1. Past de rit nog in dat dagdeel? De duur van de stops die er al staan plus
 *      reistijd, tegen de capaciteit uit de config.
 *   2. Rijden wij die dag toch al langs? Niet als straal maar als invoegkosten,
 *      zie insertionCost(). Een klant in Gorinchem op een Utrechtdag kost ons
 *      een fractie van een eigen rit, ook al is hij ver weg.
 *   3. Houden wij genoeg ruimte over voor spoed? Elke dag buiten het
 *      spoedvenster houdt een reservering vrij, zodat een klant die morgen belt
 *      en bereid is te betalen ook echt geholpen kan worden.
 *
 * Vraag 2 stuurt de planning zonder aan de prijs te komen. De goede dagen komen
 * bovenaan te staan en worden aanbevolen; kiest de klant er een, dan scheelt dat
 * ons een rit. Dat voordeel valt in onze marge en niet in een wisselende
 * rekening, want een prijs die afhangt van hoe vol de agenda toevallig stond
 * straft precies de klant die het vroegst boekt.
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

    /**
     * Het dagdeel waar een opgeslagen venster in valt.
     *
     * pickup_window kent twee vormen. De klant kiest een dagdeel ("ochtend"),
     * maar zodra iemand op de planning een tijd afspreekt staat er een klok in,
     * zoals "11:00-12:00". Beide zijn geldig, zie de regex in OrderController.
     *
     * Voor de capaciteit telt alleen in welk dagdeel de stop valt. Zonder deze
     * vertaling vergelijkt de teller "ochtend" met "11:00-12:00", vindt niets, en
     * meldt een lege ochtend terwijl er twee ritten in staan. Het uur waarop de
     * rit begint bepaalt het dagdeel, met dezelfde grenzen als WINDOW_LABELS.
     */
    public static function bucket(?string $window): string
    {
        if (! $window) {
            return 'flexibel';
        }

        if (isset(self::WINDOW_LABELS[$window])) {
            return $window;
        }

        if (preg_match('/^(\d{1,2}):\d{2}/', $window, $m)) {
            $hour = (int) $m[1];

            if ($hour < 12) {
                return 'ochtend';
            }

            return $hour < 17 ? 'middag' : 'avond';
        }

        // Iets wat wij niet herkennen bezet de dag wel maar geen dagdeel. Beter
        // een stop die te ruim telt dan een stop die verdwijnt.
        return 'flexibel';
    }

    private int $geocodeBudget = self::GEOCODE_BUDGET;

    /**
     * Alle momenten binnen de horizon, beschikbaar of niet.
     *
     * De niet-beschikbare gaan mee omdat de admin wil zien waaróm een dag niet
     * kan: vol, of nog binnen de wachttijd van de gratis optie. De klantpagina
     * filtert ze er weer uit, zie days().
     *
     * @return array{
     *     point: array{lat: float, lon: float}|null,
     *     depot_km: float|null,
     *     duration_minutes: int,
     *     from: string,
     *     until: string,
     *     not_before: string,
     *     rush_until: string|null,
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

        $notBefore = $this->notBefore($order, $depotKm);

        $rushUntil = now()->startOfDay()->addDays((int) config('desnipperaar.planning.rush_days'));

        // Wie voor spoed betaald heeft krijgt ook alleen spoeddagen te zien. Hem
        // een moment over drie weken aanbieden zou de toeslag tot een lege
        // rekening maken. Daarom mag hij de reservering opmaken die wij voor hem
        // hebben vrijgehouden, en niets daarbuiten.
        $rushOrder = $order->pickup_choice === 'spoed';
        if ($rushOrder && $rushUntil->lt($until)) {
            $until = $rushUntil->copy();
        }

        $stopsByDate = $this->plannedStops($from, $until, $order->id);

        $ctx = [
            'point'      => $point,
            'depot_km'   => $depotKm,
            'duration'   => $duration,
            'not_before' => $notBefore,
            'rush_until' => $rushUntil,
        ];

        $slots = [];
        for ($day = $from->copy(); $day->lte($until); $day->addDay()) {
            foreach ($this->slotsForDay($day, $stopsByDate, $ctx) as $slot) {
                $slots[] = $slot;
            }
        }

        return [
            'point'            => $point,
            'depot_km'         => $depotKm === null ? null : round($depotKm, 1),
            'duration_minutes' => $duration,
            'from'             => $from->toDateString(),
            'until'            => $until->toDateString(),
            'not_before'       => $notBefore->toDateString(),
            'rush_until'       => $rushUntil->toDateString(),
            'slots'            => $slots,
        ];
    }

    /**
     * De vroegste dag die deze klant mag kiezen.
     *
     * Buiten regio Amsterdam heeft hij op /order een keuze gemaakt. Koos hij
     * gratis, dan hoort daar de wachttijd bij die op diezelfde pagina beloofd is:
     * wij combineren zijn rit met een andere rit in de buurt, en die tijd hebben
     * wij nodig om dat te laten gebeuren. Koos hij betaald, dan heeft hij die
     * wachttijd afgekocht en mag hij vanaf de eerste rijdag.
     *
     * Binnen de regio bestaat de keuze niet. Daar is ophalen gratis zonder
     * voorwaarde, dus daar geldt ook geen wachttijd.
     */
    public function notBefore(Order $order, ?float $depotKm): Carbon
    {
        $earliest = $this->firstOfferableDate();

        // Weten wij de afstand niet, dan houden wij niemand tegen. Een wachttijd
        // opleggen omdat een geocodering mislukte zou een klant uit Amsterdam-Noord
        // twee weken laten wachten op een rit die gratis is en meteen kan.
        if ($depotKm === null) {
            return $earliest;
        }

        $inRegion = $depotKm <= (float) config('desnipperaar.planning.region_km');

        if ($inRegion
            || in_array($order->pickup_choice, ['sooner', 'spoed'], true)
            || (float) ($order->pickup_cost ?? 0) > 0) {
            return $earliest;
        }

        $wait = now()->startOfDay()->addDays((int) config('desnipperaar.planning.free_wait_days'));

        return $wait->gt($earliest) ? $wait : $earliest;
    }

    /**
     * Alles wat er te kiezen valt, op datum.
     *
     * Wij filteren hier niets weg op prijs. Een eerdere versie liet alleen de
     * momenten zien die de klant niets extra's kostten, en dat leverde precies
     * niets op voor een klant buiten de regio: die kreeg een lege pagina. Beter
     * laat je elke dag zien met wat hij kost. De klant kiest zelf een dag, ziet
     * de prijs erbij, en gaat vanzelf naar de dag waarop wij toch al zijn kant
     * op rijden, want dat is de goedkope dag. Dat sturen op prijs is het hele
     * doel; wegfilteren maakte dat juist onmogelijk.
     *
     * @param  array<int, array<string, mixed>>  $slots
     * @return array<int, array<string, mixed>>
     */
    public function available(array $slots): array
    {
        $open = array_values(array_filter($slots, fn (array $s) => $s['available']));

        usort($open, fn (array $a, array $b) => [$a['date'], $a['window']] <=> [$b['date'], $b['window']]);

        return $open;
    }

    /**
     * Per dag één regel, met de dagdelen die nog kunnen. Dit is wat de klant te
     * zien krijgt.
     *
     * De ophaalprijs staat er niet per dag bij. Die hangt aan het adres en is bij
     * het bestellen al vastgelegd, dus hij is voor elke dag gelijk; hem per dag
     * herhalen suggereert een keuze die er niet is. Wat er wel per dag bij staat:
     *
     *   - on_route: rijden wij die dag toch al bij de klant langs. Zonder bedrag,
     *     want het scheelt hem niets. Het bepaalt wel welke momenten wij als
     *     eerste voorstellen, zie bestSlots().
     *
     * @param  array<int, array<string, mixed>>  $slots
     * @return array<int, array<string, mixed>>
     */
    public function days(array $slots): array
    {
        $days = [];

        foreach ($this->available($slots) as $slot) {
            $date = $slot['date'];

            if (! isset($days[$date])) {
                $days[$date] = [
                    'date'      => $date,
                    'weekday'   => $slot['weekday'],
                    'on_route'  => $slot['on_route'],
                    'detour_km' => $slot['detour_km'],
                    'windows'   => [],
                ];
            }

            $days[$date]['windows'][] = [
                'window' => $slot['window'],
                'label'  => $slot['window_label'],
            ];
        }

        return array_values($days);
    }

    /**
     * De paar momenten die wij daadwerkelijk voorstellen.
     *
     * Een lijst met alle vrije uren van vier weken is geen keuze maar huiswerk:
     * dat zijn tientallen regels waar niemand doorheen leest. Wij kiezen er drie
     * en zetten daar de rest achter weg. Past er niets bij, dan is bellen sneller
     * dan scrollen.
     *
     * De volgorde komt van de omweg en niet van de datum, want de vraag is niet
     * wanneer het kan maar wanneer het slim is. Bij een gelijke omweg wint de
     * vroegste dag, en binnen een dag het vroegste uur.
     *
     * Hooguit één voorstel per dag. Drie uurblokken op dezelfde ochtend zijn geen
     * drie keuzes, want wie die dag niet kan heeft aan alle drie niets.
     *
     * @param  array<int, array<string, mixed>>  $slots
     * @return array<int, array<string, mixed>>
     */
    public function bestSlots(array $slots, int $limit = 3, bool $soonestFirst = false): array
    {
        $open = $this->available($slots);

        // Voor een spoedklant telt alleen hoe snel het kan. Hij heeft juist
        // betaald om niet te hoeven wachten op een dag die ons goed uitkomt.
        if ($soonestFirst) {
            usort($open, fn (array $a, array $b) => [$a['date'], $a['window']] <=> [$b['date'], $b['window']]);
        } else {
            usort($open, fn (array $a, array $b) => [
                $a['detour_km'] ?? PHP_FLOAT_MAX, $a['date'], $a['window'],
            ] <=> [
                $b['detour_km'] ?? PHP_FLOAT_MAX, $b['date'], $b['window'],
            ]);
        }

        $best = [];
        $seenDays = [];

        foreach ($open as $slot) {
            if (isset($seenDays[$slot['date']])) {
                continue;
            }
            $seenDays[$slot['date']] = true;
            $best[] = $slot;

            if (count($best) >= $limit) {
                break;
            }
        }

        return $best;
    }

    /**
     * Het moment zelf, of null als het niet (meer) kan.
     *
     * De klantpagina roept dit vlak voor het opslaan nog eens aan. Tussen het
     * kiezen en het bevestigen kan er een andere ophaling op die dag zijn
     * gepland, en dan is het moment weg of duurder geworden. Wij houden geen
     * reservering aan: bij dit aantal ritten per dag is opnieuw narekenen
     * eenvoudiger dan een tabel met vervallende claims, en het antwoord is
     * altijd actueel.
     *
     * @param  array<int, array<string, mixed>>  $slots
     * @return array<string, mixed>|null
     */
    public function find(array $slots, string $date, string $window): ?array
    {
        foreach ($this->available($slots) as $slot) {
            if ($slot['date'] === $date && $slot['window'] === $window) {
                return $slot;
            }
        }

        return null;
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
    private function slotsForDay(Carbon $day, array $stopsByDate, array $ctx): array
    {
        $hours = $this->hoursForDay($day);
        if (! $hours || ! WorkingDays::isWorkingDay($day)) {
            return [];
        }

        $point    = $ctx['point'];
        $depotKm  = $ctx['depot_km'];
        $duration = $ctx['duration'];

        $stops = $stopsByDate[$day->toDateString()] ?? [];

        // Wat kost het om deze klant in de rit van deze dag te schuiven? Over
        // alle dagdelen heen: rijden wij die dag naar Utrecht, dan rijden wij die
        // dag naar Utrecht, ook als dat 's ochtends was en de klant 's middags kan.
        $insertion = $this->insertionCost($point, $stops);
        $detourKm = $insertion['detour_km'] ?? null;
        $onRoute = $detourKm !== null
            && $detourKm <= (float) config('desnipperaar.planning.on_route_detour_km');

        // Reistijd naar rato van de omweg. Een klant die pal op de route ligt
        // kost een paar minuten, een eigen rit kost het volle bedrag. De bovengrens
        // houdt een verre rit binnen wat een dagdeel aankan: het dagdeel zegt
        // wanneer wij aankomen, niet hoe lang wij onderweg waren.
        $travelMax = (int) config('desnipperaar.planning.travel_minutes');
        $travel = $detourKm === null
            ? $travelMax
            : (int) round(min(
                $travelMax,
                max(
                    (int) config('desnipperaar.planning.travel_minutes_nearby'),
                    $detourKm * (float) config('desnipperaar.planning.minutes_per_km')
                )
            ));

        $needed = $duration + $travel;

        // Hoeveel aaneengesloten uren deze stop kost. Een gewone ophaling van een
        // half uur plus rijtijd past in één blok; duurt hij langer, dan houden wij
        // het volgende blok er ook voor vrij.
        $slotMinutes = max(1, (int) config('desnipperaar.planning.slot_minutes'));
        $needed = $duration + $travel;
        $blocks = max(1, (int) ceil($needed / $slotMinutes));

        // Welke uren zijn al bezet, zie occupancy(). Daar zit ook de vertaling van
        // een oude boeking op dagdeel naar een concreet uur.
        $taken = $this->occupancy($stops, $hours);

        $rush = $ctx['rush_until'] !== null && $day->lte($ctx['rush_until']);

        // Buiten het spoedvenster houden wij een paar blokken leeg. Anders loopt
        // de agenda weken vooruit vol met ritten die geen haast hadden, en staan
        // wij met lege handen zodra iemand overmorgen geholpen wil worden. De
        // reservering schuift met de tijd mee en valt vanzelf vrij.
        $reserve = $rush ? 0 : (int) config('desnipperaar.planning.rush_reserve_slots');
        $freeCount = count(array_filter($hours, fn (string $h) => ! isset($taken[$h])));

        // De wachttijd van de gratis optie. Vóór deze datum bestaat de dag wel,
        // maar niet voor deze klant.
        $tooEarly = $day->lt($ctx['not_before']);

        [$cost, $costBasis] = $this->internalCost($depotKm, $detourKm);

        $slots = [];
        foreach ($hours as $i => $hour) {
            // Past de rit vanaf dit uur, inclusief de blokken die hij uitloopt?
            $fits = true;
            for ($b = 0; $b < $blocks; $b++) {
                if (! isset($hours[$i + $b]) || isset($taken[$hours[$i + $b]])) {
                    $fits = false;
                    break;
                }
            }

            $reason = null;
            if ($tooEarly) {
                $reason = 'wachttijd gratis ophalen';
            } elseif (isset($taken[$hour])) {
                $reason = 'bezet door '.$taken[$hour];
            } elseif (! $fits) {
                $reason = 'te weinig aaneengesloten tijd';
            } elseif ($freeCount - $blocks < $reserve) {
                $reason = 'laatste ruimte, gereserveerd voor spoed';
            }

            $slots[] = [
                'date'           => $day->toDateString(),
                'weekday'        => $day->locale('nl')->translatedFormat('l'),
                'window'         => $hour,
                'window_label'   => str_replace('-', ' – ', $hour),
                'available'      => $reason === null,
                'reason'         => $reason,
                'blocks'         => $blocks,
                'needed_minutes' => $needed,
                'free_slots'     => $freeCount,
                'reserved_slots' => $reserve,
                'day_stops'      => count($stops),
                'taken_by'       => $taken[$hour] ?? null,
                'on_route'       => $onRoute,
                'detour_km'      => $detourKm === null ? null : round($detourKm, 1),
                'via_label'      => $insertion['via'] ?? null,
                'nearest_km'     => isset($insertion['nearest_km']) && $insertion['nearest_km'] !== null
                    ? round($insertion['nearest_km'], 1)
                    : null,
                'rush'           => $rush,
                'cost'           => $cost,
                'cost_basis'     => $costBasis,
            ];
        }

        return $slots;
    }

    /**
     * De uurblokken die wij op deze dag rijden, als "08:00-09:00".
     *
     * @return array<int, string>
     */
    private function hoursForDay(Carbon $day): array
    {
        $ranges = config('desnipperaar.planning.week')[$day->dayOfWeekIso] ?? [];
        $step   = max(1, (int) config('desnipperaar.planning.slot_minutes'));
        $hours  = [];

        foreach ((array) $ranges as $range) {
            if (! preg_match('/^(\d{1,2}):(\d{2})-(\d{1,2}):(\d{2})$/', (string) $range, $m)) {
                continue;
            }

            $start = ((int) $m[1]) * 60 + (int) $m[2];
            $end   = ((int) $m[3]) * 60 + (int) $m[4];

            for ($t = $start; $t + $step <= $end; $t += $step) {
                $hours[] = sprintf('%02d:%02d-%02d:%02d',
                    intdiv($t, 60), $t % 60,
                    intdiv($t + $step, 60), ($t + $step) % 60);
            }
        }

        return $hours;
    }

    /**
     * Welke uurblokken op een dag al bezet zijn, met het kenmerk van de rit die
     * er staat.
     *
     * Twee soorten boekingen door elkaar. Een rit met een afgesproken tijd zegt
     * zelf welke uren hij inneemt. Een oudere boeking staat op een dagdeel of op
     * flexibel en zegt alleen dát hij die dag rijdt. Zo'n rit ergens neerzetten
     * moeten wij toch doen, anders bieden wij een uur aan dat in werkelijkheid
     * bezet is. Wij schuiven hem naar het eerste vrije uur binnen zijn dagdeel;
     * flexibel mag de hele dag. Dat is een aanname, maar wel de voorzichtige
     * kant: liever een uur te weinig aanbieden dan een klant dubbel inplannen.
     *
     * @param  array<int, array<string, mixed>>  $stops
     * @param  array<int, string>  $hours
     * @return array<string, string>
     */
    private function occupancy(array $stops, array $hours): array
    {
        $slotMinutes = max(1, (int) config('desnipperaar.planning.slot_minutes'));
        $taken = [];

        $minutesOf = function (string $hour): int {
            [$h, $m] = explode(':', explode('-', $hour)[0]);

            return ((int) $h) * 60 + (int) $m;
        };

        // Eerst de ritten met een echte tijd. Die hebben voorrang op de rest,
        // want zij hebben een afspraak en de anderen alleen een voorkeur.
        $loose = [];
        foreach ($stops as $stop) {
            $raw = $stop['window_raw'] ?? null;

            if (! $raw || ! preg_match('/^(\d{1,2}):(\d{2})-(\d{1,2}):(\d{2})$/', $raw, $m)) {
                $loose[] = $stop;
                continue;
            }

            $from = ((int) $m[1]) * 60 + (int) $m[2];
            $to   = max($from + $slotMinutes, ((int) $m[3]) * 60 + (int) $m[4]);

            foreach ($hours as $hour) {
                $at = $minutesOf($hour);
                if ($at >= $from && $at < $to) {
                    $taken[$hour] = $stop['label'];
                }
            }
        }

        // En dan wat er zonder tijd in staat, in het eerste vrije uur dat bij zijn
        // dagdeel past.
        $bounds = [
            'ochtend'  => [0, 12 * 60],
            'middag'   => [12 * 60, 17 * 60],
            'avond'    => [17 * 60, 24 * 60],
            'flexibel' => [0, 24 * 60],
        ];

        foreach ($loose as $stop) {
            [$from, $to] = $bounds[$stop['window']] ?? $bounds['flexibel'];

            foreach ($hours as $hour) {
                $at = $minutesOf($hour);
                if ($at >= $from && $at < $to && ! isset($taken[$hour])) {
                    $taken[$hour] = $stop['label'];
                    break;
                }
            }
        }

        return $taken;
    }

    /**
     * Wat deze rit ONS op deze dag kost. Dit is geen klantprijs.
     *
     * Wat de klant betaalt hangt af van zijn adres en staat al vast bij het
     * bestellen. Dat moet ook: zou de prijs meebewegen met hoe vol de agenda
     * toevallig staat, dan betaalt de klant die als eerste boekt het volle pond
     * voor een lege dag, terwijl de buurman die een week later op diezelfde dag
     * meelift bijna niets betaalt. Zelfde dienst, zelfde adres, andere rekening,
     * puur door timing. Dat valt niet uit te leggen en het leert klanten om te
     * wachten met boeken, precies het omgekeerde van wat een planning nodig heeft.
     *
     * Dit getal is er dus voor ons: op het planbord zie je eraan welke dag deze
     * rit goedkoop maakt. De winst van slim plannen zit in onze marge, niet in
     * een wisselende prijs voor de klant.
     *
     * @return array{0: float|null, 1: string|null}
     */
    private function internalCost(?float $depotKm, ?float $detourKm): array
    {
        if ($depotKm === null) {
            return [null, null];
        }

        $perKm    = (float) config('desnipperaar.planning.per_km');
        $regionKm = (float) config('desnipperaar.planning.region_km');

        if ($depotKm <= $regionKm) {
            return [0.0, 'regio'];
        }

        // Wat een rit alleen voor deze klant kost. Zelfde grondslag als /order:
        // de eerste twintig kilometer zijn de regio en die rekenen wij niet, wat
        // erboven zit wel. Dat is de bovengrens, want meer dan apart rijden kan
        // een rit ons nooit kosten.
        $ownTrip = max(0.0, $depotKm - $regionKm) * $perKm;

        if ($detourKm === null) {
            return [round($ownTrip, 2), 'losse rit'];
        }

        // Scheelt invoegen niet echt iets ten opzichte van heen en terug rijden,
        // dan is het geen omweg maar gewoon een eigen rit. Zonder route om bij
        // aan te haken komt de invoegberekening vanzelf op de eigen rit uit, en
        // dan zou een halve kilometer meetverschil het ten onrechte "omweg"
        // noemen. De grens staat in kilometers en niet in centen, want dit gaat
        // over de rit en niet over de afronding.
        $ownTripKm = $depotKm * 2;
        if ($ownTripKm - $detourKm < 1.0) {
            return [round($ownTrip, 2), 'losse rit'];
        }

        // Ligt de klant op de route, dan rekenen wij de omweg die hij ons
        // werkelijk kost en niet de afstand tot het depot. Een klant in Gorinchem
        // op een dag dat wij naar Utrecht rijden betaalt de omweg vanaf Utrecht,
        // niet de hele reis vanaf Amsterdam.
        //
        // Halveren om op dezelfde grondslag uit te komen als het tarief. Dat
        // rekent enkele reis, terwijl een omweg de extra kilometers heen én terug
        // zijn. Zonder die deling zou een rit op de route per gereden kilometer
        // zwaarder tellen dan een eigen rit, en dat is verkeerd om.
        //
        // Nooit boven de eigen rit uitkomen. Die kent de regiokorting van de
        // eerste twintig kilometer en de omweg niet, dus bij een korte rit kan
        // meeliften op papier duurder uitvallen dan apart rijden. Dan rijden wij
        // gewoon apart, en dan hoort er ook "losse rit" te staan: een bedrag met
        // het label "omweg" dat toevallig gelijk is aan de eigen rit leest als
        // een omweg die niets oplevert, terwijl wij die omweg juist niet maken.
        $viaRoute = round(($detourKm / 2) * $perKm, 2);
        $own      = round($ownTrip, 2);

        return $viaRoute < $own ? [$viaRoute, 'omweg'] : [$own, 'losse rit'];
    }

    /**
     * Hoeveel extra kilometers kost het om deze klant in de rit van die dag te
     * schuiven.
     *
     * Dit is de vraag die ertoe doet, en niet "hoe ver ligt de dichtstbijzijnde
     * stop". Een klant in Gorinchem ligt 35 km van een stop in Utrecht en zou op
     * afstand afvallen, terwijl hij pal op de route Amsterdam-Utrecht ligt: hem
     * meenemen kost een stuk minder dan een eigen rit. Andersom kan een stop op
     * 10 km duur zijn als hij de verkeerde kant op ligt.
     *
     * Daarom rekenen wij met invoegkosten. Wij benaderen de rit van die dag als
     * depot -> stops -> depot, en zoeken het goedkoopste punt om de nieuwe klant
     * ertussen te schuiven:
     *
     *     omweg = afstand(vorige, nieuw) + afstand(nieuw, volgende)
     *             - afstand(vorige, volgende)
     *
     * De rit terug naar het depot telt mee, want die maken wij ook. Ligt de klant
     * precies tussen twee punten in, dan is de omweg bijna nul.
     *
     * Zonder geplande stops is er geen rit om bij aan te haken. Dan is de omweg
     * de hele rit heen en terug: dat is precies wat een eigen rit kost.
     *
     * @param  array<int, array<string, mixed>>  $stops
     * @return array{detour_km: float, via: string|null, nearest_km: float|null}|null
     */
    private function insertionCost(?array $point, array $stops): ?array
    {
        if (! $point) {
            return null;
        }

        $depot = Geocoder::depot();
        $placed = array_values(array_filter($stops, fn (array $s) => $s['point']));

        if (! $placed) {
            return [
                'detour_km'  => Geocoder::roadKm($depot, $point) * 2,
                'via'        => null,
                'nearest_km' => null,
            ];
        }

        $route = $this->routeOrder($depot, $placed);

        // Elke schakel van depot -> ... -> depot is een plek om in te voegen.
        // De laatste schakel eindigt in het depot, vandaar de null als sluitstuk.
        $best = null;
        $nearest = null;
        $prev = ['point' => $depot, 'label' => null];

        foreach (array_merge($route, [['point' => $depot, 'label' => null]]) as $next) {
            $toNew   = Geocoder::roadKm($prev['point'], $point);
            $fromNew = Geocoder::roadKm($point, $next['point']);
            $direct  = Geocoder::roadKm($prev['point'], $next['point']);
            $detour  = max(0.0, $toNew + $fromNew - $direct);

            // Waar haken wij op aan: de dichtstbijzijnde van de twee uiteinden,
            // want dat is de stop die de klant herkent als "u zat in de buurt".
            $via = $fromNew <= $toNew ? $next['label'] : $prev['label'];

            if ($best === null || $detour < $best['detour_km']) {
                $best = ['detour_km' => $detour, 'via' => $via];
            }
            if ($next['label'] !== null && ($nearest === null || $fromNew < $nearest)) {
                $nearest = $fromNew;
            }

            $prev = $next;
        }

        $best['nearest_km'] = $nearest;

        return $best;
    }

    /**
     * De volgorde waarin wij de stops van een dag waarschijnlijk rijden.
     *
     * Er staat nergens een volgorde vast, dus benaderen wij hem: steeds de
     * dichtstbijzijnde volgende stop, vanaf het depot. Voor een handvol stops
     * per dag zit dat er dicht genoeg bij, en de invoegkosten hebben alleen een
     * plausibele route nodig, geen optimale.
     *
     * @param  array<int, array<string, mixed>>  $stops
     * @return array<int, array<string, mixed>>
     */
    private function routeOrder(array $depot, array $stops): array
    {
        $remaining = $stops;
        $route = [];
        $cursor = $depot;

        while ($remaining) {
            $bestIndex = 0;
            $bestKm = null;
            foreach ($remaining as $i => $stop) {
                $km = Geocoder::roadKm($cursor, $stop['point']);
                if ($bestKm === null || $km < $bestKm) {
                    $bestKm = $km;
                    $bestIndex = $i;
                }
            }
            $route[] = $remaining[$bestIndex];
            $cursor = $remaining[$bestIndex]['point'];
            array_splice($remaining, $bestIndex, 1);
        }

        return $route;
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
                'window'  => self::bucket($planned->pickup_window),
                'window_raw' => $planned->pickup_window,
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
                'window'  => self::bucket($bon->planned_window),
                'window_raw' => $bon->planned_window,
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
