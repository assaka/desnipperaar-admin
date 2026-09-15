<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Het ophaaladres, als dat een ander adres is dan dat van de klant.
 *
 * Tot nu toe was er één adres per order en deed dat alles: de factuur ging
 * erheen en de wagen ook. Dat klopt niet bij een hoofdkantoor dat opdracht geeft
 * voor een filiaal, bij een beheerder die voor een pand tekent en bij een
 * verhuizing waar het archief al ergens anders staat. Die ophalingen gingen tot
 * nu toe per mail rechtgezet, na de bevestiging, met het verkeerde adres op de
 * ophaalbon.
 *
 * Leeg betekent "hetzelfde adres als de klant". Zo houden bestaande orders hun
 * betekenis en hoeft er niets gevuld te worden voor de gewone situatie, die de
 * meeste is. Order::pickupLocation() kiest tussen de twee.
 *
 * Het offerteformulier vraagt het als één regel vrije tekst, want daar is nog
 * geen postcodecontrole en soms nog geen definitief adres. Die regel komt in
 * pickup_address te staan en laat postcode en plaats leeg.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pickup_address', 255)->nullable()->after('customer_city');
            $table->string('pickup_postcode', 12)->nullable()->after('pickup_address');
            $table->string('pickup_city', 100)->nullable()->after('pickup_postcode');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_address', 'pickup_postcode', 'pickup_city']);
        });
    }
};
