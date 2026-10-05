<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De klant bevestigt dat hij de ophaalmail heeft ontvangen.
 *
 * Naast het tijdstip staat het moment waarvoor hij bevestigde. Wordt de ophaling
 * daarna verzet, dan gaat er een nieuwe mail uit en geldt de oude bevestiging
 * niet meer. Door het moment te bewaren in plaats van de kolom leeg te maken bij
 * elke verzending, hoeft geen van de plekken die de mail versturen eraan te
 * denken. Order::pickupReceiptConfirmed() vergelijkt de twee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('pickup_receipt_confirmed_at')->nullable()->after('pickup_plan_invited_at');
            $table->string('pickup_receipt_moment', 40)->nullable()->after('pickup_receipt_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_receipt_confirmed_at', 'pickup_receipt_moment']);
        });
    }
};
