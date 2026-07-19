<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vaste ophaaldag van een abonnement, als ISO-weekdag (1 = maandag t/m 5 = vrijdag).
 *
 * Het ritme werd geteld vanaf de ingangsdatum, waardoor de ophaaldag een gevolg
 * was van de dag waarop iemand toevallig op goedkeuren klikte. Een klant wil een
 * vaste dag afspreken, geen datum die uit de administratie rolt.
 *
 * Alle intervallen zijn een veelvoud van zeven dagen, dus zodra de reeks op een
 * weekdag is verankerd blijft hij daarop staan. 2x per week gebruikt dit veld
 * niet, dat is altijd maandag en donderdag.
 *
 * Bestaande abonnementen krijgen de weekdag van hun ingangsdatum, zodat er niets
 * verschuift voor wie al loopt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('sub_pickup_weekday')->nullable()->after('sub_freq');
        });

        DB::statement("
            UPDATE orders
            SET sub_pickup_weekday = EXTRACT(ISODOW FROM sub_active_from)
            WHERE type = 'abonnement' AND sub_active_from IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('sub_pickup_weekday');
        });
    }
};
