<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Doorlopende nummering per soort en per jaar.
 *
 * Alle nummers in deze applicatie werden bepaald door de laatste rij op te
 * zoeken en er één bij op te tellen. Dat gaat op twee manieren mis:
 *
 * - Verwijder je de nieuwste rij, dan komt zijn nummer weer vrij en krijgt een
 *   volgende rij hetzelfde nummer. Dat is echt gebeurd: na het terugzetten van
 *   een abonnement kreeg een nieuwe bezorgrit opnieuw DS-2026-0165. Bij een
 *   factuur of certificaat zou dat erger zijn, want die gaan de deur uit en
 *   staan in de boekhouding.
 * - Twee gelijktijdige aanvragen lezen dezelfde stand en krijgen hetzelfde
 *   nummer.
 *
 * Een teller die alleen omhoog gaat lost allebei op. Het ophogen zit in één
 * statement met RETURNING, dus twee processen kunnen niet dezelfde waarde
 * krijgen. Gaten in de reeks zijn hierbij normaal en onschadelijk: een
 * hergebruikt nummer is dat niet.
 */
class NumberSequence
{
    /**
     * Volgend nummer als PREFIX-JAAR-0001.
     *
     * $start geldt alleen als de teller voor dit jaar nog niet bestaat. Bestaande
     * reeksen worden bij de migratie ingelezen, dus dit raakt alleen een nieuw
     * jaar of een nieuw soort nummer.
     */
    public static function next(string $prefix, int $start): string
    {
        $year = now()->year;
        $key  = $prefix.':'.$year;

        $row = DB::selectOne(
            'INSERT INTO number_counters (key, last_value, created_at, updated_at)
             VALUES (?, ?, now(), now())
             ON CONFLICT (key) DO UPDATE SET last_value = number_counters.last_value + 1, updated_at = now()
             RETURNING last_value',
            [$key, $start]
        );

        return sprintf('%s-%d-%04d', $prefix, $year, (int) $row->last_value);
    }
}
