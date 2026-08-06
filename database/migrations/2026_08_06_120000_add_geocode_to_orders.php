<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Coördinaten op de order, zodat de planner kan zien welke ophalingen bij elkaar
 * in de buurt liggen.
 *
 * De publieke site rekende de afstand al uit voor de ophaalprijs, maar bewaarde
 * alleen het aantal kilometers. Daarmee weet je hoe ver een klant van het depot
 * ligt en niet of twee klanten naast elkaar wonen, en dat laatste is precies wat
 * een route goedkoop maakt. Vandaar het punt zelf en niet de afstand.
 *
 * Het punt is het middelpunt van de postcode, niet het huisnummer. Op een paar
 * honderd meter na klopt dat, en clusteren doen wij op kilometers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('lat', 9, 6)->nullable()->after('customer_city');
            $table->decimal('lon', 9, 6)->nullable()->after('lat');
            // Wanneer wij het punt hebben opgezocht. Leeg terwijl lat/lon leeg
            // zijn betekent "nog niet geprobeerd"; gevuld met lege lat/lon
            // betekent "opgezocht en niet gevonden", zodat een onvindbare
            // postcode niet elke pagina-weergave opnieuw PDOK belt.
            $table->timestamp('geocoded_at')->nullable()->after('lon');

            // Gezet als de klant zijn ophaalmoment zelf via de planpagina heeft
            // gekozen. Puur informatief: op de orderpagina wil je kunnen zien of
            // die datum van ons komt of van de klant.
            $table->timestamp('pickup_planned_by_customer_at')->nullable()->after('pickup_note');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lon', 'geocoded_at', 'pickup_planned_by_customer_at']);
        });
    }
};
