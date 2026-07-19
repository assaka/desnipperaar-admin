<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Losse teller voor ordernummers, per soort en per jaar.
 *
 * De nummers werden bepaald door de laatste rij op te zoeken en er één bij op te
 * tellen. Twee problemen: verwijder je de nieuwste order, dan komt zijn nummer
 * weer vrij en krijgt een volgende order hetzelfde nummer, en twee gelijktijdige
 * aanvragen lezen dezelfde stand en krijgen allebei hetzelfde nummer.
 *
 * Het eerste is echt gebeurd: na het terugzetten van een abonnement kreeg de
 * nieuwe bezorgrit opnieuw DS-2026-0165, hetzelfde nummer als de verwijderde rit.
 *
 * Een teller die alleen omhoog gaat lost allebei op. Het ophogen gebeurt in één
 * statement met RETURNING, dus twee processen kunnen niet dezelfde waarde
 * krijgen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_counters', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->unsignedInteger('last_value');
            $table->timestamps();
        });

        // Beginstand overnemen van wat er al ligt, zodat bestaande nummers nooit
        // opnieuw worden uitgegeven. Per prefix en jaar de hoogste bestaande
        // volgnummers pakken uit zowel order_number als quote_reference.
        $rows = DB::select("
            SELECT prefix, yr, MAX(seq) AS max_seq FROM (
                SELECT split_part(order_number, '-', 1) AS prefix,
                       split_part(order_number, '-', 2) AS yr,
                       CAST(split_part(order_number, '-', 3) AS INTEGER) AS seq
                FROM orders WHERE order_number ~ '^[A-Z]+-[0-9]{4}-[0-9]+$'
                UNION ALL
                SELECT split_part(quote_reference, '-', 1),
                       split_part(quote_reference, '-', 2),
                       CAST(split_part(quote_reference, '-', 3) AS INTEGER)
                FROM orders WHERE quote_reference ~ '^[A-Z]+-[0-9]{4}-[0-9]+$'
            ) t GROUP BY prefix, yr
        ");

        foreach ($rows as $r) {
            DB::table('number_counters')->insert([
                'key'        => $r->prefix.':'.$r->yr,
                'last_value' => (int) $r->max_seq,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('number_counters');
    }
};
