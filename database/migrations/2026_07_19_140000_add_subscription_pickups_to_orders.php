<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Losse ophalingen onder een abonnement.
 *
 * Een abonnement is een contract, geen ophaling: het heeft geen ophaaldatum en
 * komt dus nooit op het planbord. De werkelijke bezoeken worden aparte orders,
 * met een ophaaldatum, zodat ze gewoon meelopen in de planning, de bon en het
 * certificaat.
 *
 * subscription_scheduled_for bewaart de datum die het ritme voorschrijft, vóór
 * het verschuiven om een feestdag of weekend. Daarop staat de unique index, want
 * anders zou een verschoven ophaling bij de volgende run opnieuw aangemaakt
 * worden. Het is ook wat "ritme blijft intact" mogelijk maakt: de volgende keer
 * rekenen we door vanaf de oorspronkelijke datum, niet vanaf de verschoven.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('subscription_order_id')->nullable()->after('group_deal_id')
                ->constrained('orders')->nullOnDelete();
            $table->date('subscription_scheduled_for')->nullable()->after('subscription_order_id');
            $table->unique(['subscription_order_id', 'subscription_scheduled_for'], 'orders_subscription_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_subscription_slot_unique');
            $table->dropConstrainedForeignId('subscription_order_id');
            $table->dropColumn('subscription_scheduled_for');
        });
    }
};
