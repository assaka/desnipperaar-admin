<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Beginstand van de teller voor bon-, certificaat- en factuurnummers.
 *
 * Zonder deze seed zou de teller bij nul beginnen en zouden bestaande nummers
 * opnieuw worden uitgegeven. Bij facturen en certificaten is dat het ergst: die
 * zijn al verstuurd en staan in de boekhouding van de klant.
 *
 * Per tabel de hoogste bestaande volgnummers overnemen, per prefix en jaar.
 */
return new class extends Migration
{
    public function up(): void
    {
        $bronnen = [
            ['table' => 'bons',         'column' => 'bon_number'],
            ['table' => 'certificates', 'column' => 'certificate_number'],
            ['table' => 'invoices',     'column' => 'invoice_number'],
        ];

        foreach ($bronnen as $b) {
            $rows = DB::select("
                SELECT split_part({$b['column']}, '-', 1) AS prefix,
                       split_part({$b['column']}, '-', 2) AS yr,
                       MAX(CAST(split_part({$b['column']}, '-', 3) AS INTEGER)) AS max_seq
                FROM {$b['table']}
                WHERE {$b['column']} ~ '^[A-Z]+-[0-9]{4}-[0-9]+$'
                GROUP BY 1, 2
            ");

            foreach ($rows as $r) {
                $key = $r->prefix.':'.$r->yr;

                // Bestaat de teller al (zelfde prefix als een ander soort nummer),
                // dan de hoogste van de twee houden.
                DB::statement(
                    'INSERT INTO number_counters (key, last_value, created_at, updated_at)
                     VALUES (?, ?, now(), now())
                     ON CONFLICT (key) DO UPDATE
                     SET last_value = GREATEST(number_counters.last_value, EXCLUDED.last_value), updated_at = now()',
                    [$key, (int) $r->max_seq]
                );
            }
        }
    }

    public function down(): void
    {
        // De tellers zelf blijven staan; ze weghalen zou nummers weer vrijgeven.
    }
};
