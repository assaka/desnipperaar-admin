<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Een creditfactuur krijgt een eigen status.
 *
 * Hij kwam binnen als 'draft' en werd bij het versturen 'sent', en daarmee liep
 * hij mee in de kolommen die over innen gaan: de vervaldatum staat op de dag van
 * aanmaken, dus een verstuurde creditfactuur stond een dag later in het rood als
 * OVERDUE. Er valt niets te innen, wij betalen juist terug.
 *
 * 'credit' is een eindstatus: hij wordt niet betaald en vervalt niet. Versturen
 * laat hem staan, want mail() houdt alles wat geen concept is op zijn status.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check');
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status IN ('draft','sent','paid','canceled','credit'))");

        // Bestaande creditfacturen mee, anders staan gelijke stukken in twee
        // statussen en klopt het filter op de factuurlijst niet.
        DB::statement("UPDATE invoices SET status = 'credit' WHERE credits_invoice_id IS NOT NULL");
    }

    public function down(): void
    {
        // Terug naar waar ze stonden: verstuurd als er een sent_at is, anders concept.
        DB::statement("UPDATE invoices SET status = CASE WHEN sent_at IS NULL THEN 'draft' ELSE 'sent' END WHERE status = 'credit'");

        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check');
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status IN ('draft','sent','paid','canceled'))");
    }
};
