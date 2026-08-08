<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Een uitbetaalde creditfactuur krijgt status 'repaid'.
 *
 * 'credit' zei tot nu toe alleen dat het stuk bestaat, niet dat het geld terug
 * is. Dat is nu juist wat je in de lijst zoekt: staat er nog een bedrag open
 * naar de klant, of is de order echt van tafel. De datum van de terugboeking
 * komt in paid_at, dezelfde kolom als bij een gewone betaling, want het is
 * dezelfde vraag: wanneer is er afgerekend.
 *
 * Geen backfill. Wij weten van de bestaande creditfacturen niet of ze zijn
 * uitbetaald, en die vraag met een migratie beantwoorden is gokken in de
 * boekhouding. Ze blijven op 'credit' staan tot iemand ze afvinkt.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check');
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status IN ('draft','sent','paid','canceled','credit','repaid'))");
    }

    public function down(): void
    {
        DB::statement("UPDATE invoices SET status = 'credit' WHERE status = 'repaid'");

        DB::statement('ALTER TABLE invoices DROP CONSTRAINT IF EXISTS invoices_status_check');
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status IN ('draft','sent','paid','canceled','credit'))");
    }
};
