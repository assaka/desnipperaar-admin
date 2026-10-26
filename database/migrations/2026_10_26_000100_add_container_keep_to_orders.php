<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Een losse rolcontainer die bij de klant blijft staan.
 *
 * Normaal vult de klant de container binnen 30 minuten en gaat hij in dezelfde
 * rit mee terug. Kiest de klant op /order voor "langer laten staan", dan rekenen
 * wij een extra rit plus huur per container per maand. De eerste maand staat op
 * de orderfactuur, elke volgende maand krijgt een eigen conceptfactuur
 * (containers:invoice-rent) tot de container is opgehaald.
 *
 * container_keep_since is de dag dat de container bij de klant kwam te staan, de
 * ankerdatum van de huurmaanden. container_collected_on stopt de huur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('container_keep')->default(false)->after('container_count');
            $table->date('container_keep_since')->nullable()->after('container_keep');
            $table->date('container_collected_on')->nullable()->after('container_keep_since');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['container_keep', 'container_keep_since', 'container_collected_on']);
        });
    }
};
