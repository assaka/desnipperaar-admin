<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Weet de klant nog wat wij weten?
 *
 * Adres, aantallen en een kortingscode kunnen na het aanmaken wijzigen. De
 * bevestiging die de klant heeft, klopt dan niet meer, en dat was tot nu toe
 * alleen te zien door de order met de mail te vergelijken.
 *
 * Een eigen vlag en niet updated_at vergelijken met een verzendmoment: die
 * loopt ook op van een statuswissel of een planning, en dan zou de orderpagina
 * blijven roepen dat de klant iets moet horen terwijl er inhoudelijk niets is
 * veranderd.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('confirmation_stale')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('confirmation_stale');
        });
    }
};
