<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Nederlandse werkdagen. Wij rijden niet in het weekend en niet op een erkende
 * feestdag, dus een geplande ophaling die daarop valt schuift naar de eerstvolgende
 * werkdag.
 *
 * Pasen wordt hier zelf uitgerekend (Meeus/Jones/Butcher) in plaats van met
 * easter_date(), want die functie zit in de calendar-extensie en die is niet
 * overal aan. Een ontbrekende extensie zou hier stilletjes de helft van de
 * feestdagen laten verdwijnen.
 *
 * Bevrijdingsdag staat er bewust niet bij: dat is voor de meeste bedrijven een
 * gewone werkdag. Beter een keer te veel rijden dan een ophaling overslaan.
 */
class WorkingDays
{
    /** Erkende feestdagen die een ophaling verschuiven, als Y-m-d. */
    public static function holidays(int $year): array
    {
        $easter = self::easter($year);

        $days = [
            Carbon::create($year, 1, 1),                  // Nieuwjaarsdag
            $easter->copy()->addDay(),                     // Tweede paasdag
            self::kingsDay($year),                         // Koningsdag
            $easter->copy()->addDays(39),                  // Hemelvaartsdag
            $easter->copy()->addDays(50),                  // Tweede pinksterdag
            Carbon::create($year, 12, 25),                 // Eerste kerstdag
            Carbon::create($year, 12, 26),                 // Tweede kerstdag
        ];

        return array_map(fn (Carbon $d) => $d->toDateString(), $days);
    }

    /** Koningsdag is 27 april, maar valt die op zondag dan wordt het de 26e. */
    private static function kingsDay(int $year): Carbon
    {
        $day = Carbon::create($year, 4, 27);

        return $day->isSunday() ? $day->subDay() : $day;
    }

    /** Eerste paasdag volgens het anonieme gregoriaanse algoritme. */
    private static function easter(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day);
    }

    public static function isWorkingDay(Carbon $date): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        return ! in_array($date->toDateString(), self::holidays((int) $date->year), true);
    }

    /**
     * De datum zelf als het een werkdag is, anders dezelfde weekdag een week
     * later.
     *
     * Wij rijden vaste routes per weekdag. Valt zo'n dag op een feestdag, dan is
     * er die dag geen route, en er is geen ruimte om de rit er de dag erna bij te
     * proppen. Een week opschuiven houdt de klant op zijn eigen routedag.
     *
     * Alleen die ene keer schuift op: het ritme blijft op de oorspronkelijke data
     * doorlopen, zodat één feestdag de hele reeks niet meesleept.
     */
    public static function nextSameWeekday(Carbon $date): Carbon
    {
        $d = $date->copy()->startOfDay();

        // Ruime bovengrens; twee opeenvolgende feestdagen op dezelfde weekdag
        // komt voor (Kerst), meer dan een paar niet.
        for ($i = 0; $i < 6 && ! self::isWorkingDay($d); $i++) {
            $d->addWeek();
        }

        return $d;
    }

    /**
     * De datum zelf als het een werkdag is, anders de eerstvolgende werkdag.
     * Vooruit en niet achteruit, want eerder ophalen dan afgesproken kan betekenen
     * dat de container nog niet vol is.
     */
    public static function next(Carbon $date): Carbon
    {
        $d = $date->copy()->startOfDay();

        // Ruime bovengrens; een reeks van meer dan tien niet-werkdagen bestaat niet.
        for ($i = 0; $i < 14 && ! self::isWorkingDay($d); $i++) {
            $d->addDay();
        }

        return $d;
    }
}
