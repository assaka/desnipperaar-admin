<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Haakje voor online incasso later (Stripe of een andere aggregator).
 *
 * Bewust maar twee kolommen en verder niets. De verleiding is om nu al een
 * mandaat-, status- en webhookmodel te bedenken, maar zolang er geen koppeling
 * is weet niemand welke velden die aggregator echt nodig heeft. Wat wel vaststaat
 * is dat er een verwijzing naar een externe klant en een externe abonnementsrij
 * moet worden bewaard, en dat je die op het moment van koppelen niet wilt moeten
 * toevoegen onder tijdsdruk.
 *
 * provider staat erbij zodat wisselen of naast elkaar draaien kan zonder de
 * betekenis van de ref-kolommen te hoeven raden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('sub_billing_provider', 30)->nullable()->after('sub_last_invoiced_period');
            $table->string('sub_billing_ref', 120)->nullable()->after('sub_billing_provider');
            $table->index(['sub_billing_provider', 'sub_billing_ref'], 'orders_billing_ref_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_billing_ref_index');
            $table->dropColumn(['sub_billing_provider', 'sub_billing_ref']);
        });
    }
};
