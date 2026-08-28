<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Langs welk kanaal wij om de review hebben gevraagd, mail of WhatsApp.
 *
 * Alleen een datum is hier niet genoeg. Een appje zetten wij klaar in WhatsApp
 * en versturen wij daar met de hand, een mail gaat direct de deur uit. Wie later
 * op de orderpagina kijkt moet kunnen zien welke van de twee het was, want bij
 * een appje is "gevraagd" een stap minder zeker dan bij een mail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('review_requested_via', 16)->nullable()->after('review_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('review_requested_via');
        });
    }
};
