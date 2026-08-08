<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spoedtoeslag: het vaste bedrag voor een ophaling binnen een paar dagen.
 *
 * Bewust een eigen kolom en niet opgeteld bij pickup_cost. Die laatste is bij
 * het bestellen uit het adres berekend en staat zo in de orderbevestiging; hem
 * achteraf ophogen zou betekenen dat het bedrag in de bevestiging niet meer
 * klopt met het bedrag op de factuur. De toeslag komt uit een latere keuze van
 * de klant, hoort op de factuur als een eigen regel, en houdt zich daarom apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('pickup_rush_fee', 8, 2)->nullable()->after('pickup_choice');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('pickup_rush_fee');
        });
    }
};
