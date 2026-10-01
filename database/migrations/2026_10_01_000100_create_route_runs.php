<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Open ritten: een verre rit die toch al gereden wordt, waar klanten langs de
 * route gratis op mee mogen.
 *
 * Aanleiding is een order op 220 km die wij aannamen omdat hij groot genoeg was.
 * Die rit rijdt hoe dan ook, en elke stop onderweg die niet meer dan een paar
 * kilometer omweg kost is bijna gratis voor ons. Tot nu toe zag een klant langs
 * die route alleen de kilometerprijs of twee weken wachten.
 *
 * Een rit heeft een bestemming (postcode) en een maximale omweg. De datum mag
 * nog open staan en is alleen voor ons: verre ophalingen plannen wij met de
 * hand, en de datum spreken wij met de klant af.
 *
 * Een order die meerijdt wijst via route_run_id naar zijn rit, zodat de rit op
 * het scherm laat zien wie er al aan hangt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_runs', function (Blueprint $table) {
            $table->id();
            $table->string('label', 120);
            $table->string('destination_postcode', 12);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lon', 10, 7)->nullable();
            $table->date('run_date')->nullable();
            $table->decimal('max_detour_km', 6, 1)->default(30);
            $table->boolean('is_open')->default(true);
            $table->foreignId('anchor_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('route_run_id')->nullable()->after('pickup_choice')
                ->constrained('route_runs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('route_run_id');
        });

        Schema::dropIfExists('route_runs');
    }
};
