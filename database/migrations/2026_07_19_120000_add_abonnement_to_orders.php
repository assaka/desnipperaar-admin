<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Abonnementen krijgen dezelfde behandeling als offertes: een order met een
 * eigen type, zodat ze door dezelfde intake, dezelfde offertemail en dezelfde
 * token-acceptatie lopen. Het verschil zit in de acceptatie. Een offerte wordt
 * bij accepteren een losse order (type wordt direct), een abonnement blijft een
 * abonnement en wordt alleen actief gezet via sub_active_from.
 *
 * Postgres dwingt een enum af met een CHECK-constraint, dus die moet opnieuw
 * met de nieuwe waarde erbij. Zie ook de eerdere ingreep in
 * 2026_06_13_130000_relax_pickup_window_constraints.
 *
 * Looptijd, frequentie en prijs krijgen bewust eigen kolommen in plaats van een
 * regel in het notes-blob zoals de offerte-intake doet. Een abonnement moet je
 * kunnen sorteren, filteren en straks factureren, en dat kan niet op vrije tekst.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_type_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_type_check CHECK (type IN ('direct','quote','abonnement'))");

        Schema::table('orders', function (Blueprint $table) {
            $table->string('sub_term', 20)->nullable()->after('quote_token');      // flex | vast | jaar
            $table->string('sub_freq', 10)->nullable()->after('sub_term');         // 4w | 2w | 1w | 2pw
            $table->decimal('sub_price_excl_btw', 10, 2)->nullable()->after('sub_freq');
            $table->date('sub_active_from')->nullable()->after('sub_price_excl_btw');
            // Startpunt van de lopende contracttermijn. Bij activatie gelijk aan
            // sub_active_from, maar bij verlenging schuift dit door. De minimum-
            // termijn en de verlengdatum rekenen hiervandaan, niet vanaf de
            // oorspronkelijke startdatum, anders kan een abonnement nooit een
            // tweede termijn krijgen.
            $table->date('sub_term_started_on')->nullable()->after('sub_active_from');
            // Wanneer de verlengmail is verstuurd. Voorkomt dat de cron elke dag
            // opnieuw mailt zolang de termijn binnen een maand afloopt.
            $table->timestamp('sub_renewal_notified_at')->nullable()->after('sub_term_started_on');
            // Opzeggen gaat nooit per direct: het loopt door tot het einde van de
            // lopende maand, en niet eerder dan het einde van de minimumtermijn.
            // sub_terminated_at is het moment van opzeggen, sub_ends_on de datum
            // waarop het abonnement daadwerkelijk stopt.
            $table->timestamp('sub_terminated_at')->nullable()->after('sub_active_from');
            $table->date('sub_ends_on')->nullable()->after('sub_terminated_at');
            // Startdatum van de laatst gefactureerde periode. Maakt de facturatie-
            // cron idempotent zonder elke keer de facturentabel af te zoeken.
            $table->date('sub_last_invoiced_period')->nullable()->after('sub_ends_on');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'sub_term', 'sub_freq', 'sub_price_excl_btw', 'sub_active_from',
                'sub_term_started_on', 'sub_renewal_notified_at',
                'sub_terminated_at', 'sub_ends_on', 'sub_last_invoiced_period',
            ]);
        });

        // Bestaande abonnementen zouden de oude constraint breken. Terugzetten
        // naar direct is de enige waarde die klopt: het zijn dan losse orders.
        DB::statement("UPDATE orders SET type = 'direct' WHERE type = 'abonnement'");
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_type_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_type_check CHECK (type IN ('direct','quote'))");
    }
};
