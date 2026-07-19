<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wanneer de herinnering voor een ophaling is verstuurd.
 *
 * Staat op de order en niet op het abonnement, want een herinnering hoort bij
 * één bezoek. Zonder deze kolom zou de cron elke dag opnieuw mailen zolang de
 * ophaaldatum in het venster valt.
 *
 * Bewust algemeen genoemd: nu gebruiken alleen abonnementsophalingen het, maar
 * er is niets abonnementsspecifieks aan een herinnering voor een ophaaldag.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('pickup_reminder_sent_at')->nullable()->after('pickup_choice');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('pickup_reminder_sent_at');
        });
    }
};
