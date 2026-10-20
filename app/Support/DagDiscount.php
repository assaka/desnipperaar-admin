<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Het SnipperDag-percentage op een gegeven dag. Een geplande wijziging staat in
 * config desnipperaar.dag (next_pct + next_from) en gaat om middernacht
 * Amsterdamse tijd vanzelf in, zodat de aankondiging en de welkomstmail op de
 * goede dag omslaan zonder deploy.
 */
class DagDiscount
{
    public static function pct(?Carbon $on = null): int
    {
        $from = config('desnipperaar.dag.next_from');
        $next = self::next();
        $day  = ($on ?? now())->copy()->setTimezone('Europe/Amsterdam')->toDateString();

        return ($from && $next !== null && $day >= $from) ? $next : self::current();
    }

    /** The percentage until the planned switch. */
    public static function current(): int
    {
        return (int) (config('desnipperaar.dag.pct') ?? 35);
    }

    /** The percentage from the planned switch on, or null when none is planned. */
    public static function next(): ?int
    {
        $n = config('desnipperaar.dag.next_pct');
        return $n === null ? null : (int) $n;
    }

    public static function switchDate(): ?Carbon
    {
        $from = config('desnipperaar.dag.next_from');
        return $from ? Carbon::parse($from, 'Europe/Amsterdam') : null;
    }
}
