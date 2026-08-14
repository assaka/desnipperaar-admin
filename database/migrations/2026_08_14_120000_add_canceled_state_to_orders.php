<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Een order kan worden geannuleerd.
 *
 * Tot nu toe kon een order alleen vooruit: nieuw, bevestigd, opgehaald,
 * vernietigd, afgesloten. Een klant die belt dat het niet doorgaat, een dubbele
 * bestelling of een test moest daarom door de hele reis heen of eeuwig op nieuw
 * blijven staan, waar hij in de lijst om aandacht bleef vragen.
 *
 * "afgesloten" gebruiken was geen optie. Dat betekent dat het werk is gedaan, en
 * dat is precies het tegenovergestelde van wat hier gebeurt.
 *
 * De reden en het moment staan erbij. Zonder reden is een geannuleerde order een
 * raadsel zodra de klant er een week later over belt, en de reden gaat ook mee
 * in de mail naar de klant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('canceled_at')->nullable();
            $table->string('cancel_reason', 300)->nullable();
        });

        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_state_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_state_check CHECK (state IN ('nieuw','bevestigd','opgehaald','vernietigd','afgesloten','geannuleerd'))");
    }

    public function down(): void
    {
        // Terug naar afgesloten en niet naar nieuw: een geannuleerde order is van
        // tafel, en hem terugzetten naar nieuw zou hem opnieuw in de planning en
        // in de werklijst duwen.
        DB::statement("UPDATE orders SET state = 'afgesloten' WHERE state = 'geannuleerd'");

        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_state_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_state_check CHECK (state IN ('nieuw','bevestigd','opgehaald','vernietigd','afgesloten'))");

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['canceled_at', 'cancel_reason']);
        });
    }
};
