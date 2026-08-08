<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wanneer wij de klant hebben uitgenodigd om zelf een ophaalmoment te kiezen.
 *
 * Nodig om te zien of er al een uitnodiging uit is. Zonder dit veld weet je op de
 * orderpagina niet of je de link al gestuurd hebt, en stuur je hem twee keer of
 * juist nooit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('pickup_plan_invited_at')->nullable()->after('pickup_planned_by_customer_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('pickup_plan_invited_at');
        });
    }
};
