<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wanneer wij de klant om een review hebben gevraagd.
 *
 * Een review vraag je één keer. Zonder dit veld zie je op de orderpagina niet of
 * de vraag al uit is, en dan vraagt de volgende die de order opent het nog eens.
 * Twee keer om dezelfde gunst vragen kost meer goodwill dan de review oplevert.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('review_requested_at')->nullable()->after('pickup_plan_invited_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('review_requested_at');
        });
    }
};
