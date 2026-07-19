<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Abonnementsfacturen dekken een periode, losse facturen niet. De periode moet
 * op de factuur staan (de klant moet zien waarvoor hij betaalt) en de cron moet
 * eraan kunnen zien wat al gefactureerd is.
 *
 * De unique index op (order_id, period_start) is de echte beveiliging tegen
 * dubbel factureren. Draait de cron twee keer, of start iemand hem handmatig
 * nog eens, dan faalt de tweede insert in plaats van een tweede factuur te
 * sturen. Postgres telt NULL's niet als gelijk, dus losse facturen (period_start
 * is NULL) blijven gewoon meerdere rijen per order houden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('period_start')->nullable()->after('bon_id');
            $table->date('period_end')->nullable()->after('period_start');
            $table->unique(['order_id', 'period_start'], 'invoices_order_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_order_period_unique');
            $table->dropColumn(['period_start', 'period_end']);
        });
    }
};
