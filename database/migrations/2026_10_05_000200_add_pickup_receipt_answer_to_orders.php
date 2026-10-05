<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De knop in de ophaalmail vraagt niet alleen of de mail is aangekomen, maar of
 * het moment de klant past. Het antwoord is 'akkoord' of 'past_niet', en bij
 * past_niet kan de klant zeggen wat beter uitkomt.
 *
 * Rijen die al bevestigd waren (alleen de eerste uren na de vorige migratie)
 * tellen als akkoord.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pickup_receipt_answer', 12)->nullable()->after('pickup_receipt_moment');
            $table->text('pickup_receipt_note')->nullable()->after('pickup_receipt_answer');
        });

        DB::table('orders')->whereNotNull('pickup_receipt_confirmed_at')->update(['pickup_receipt_answer' => 'akkoord']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_receipt_answer', 'pickup_receipt_note']);
        });
    }
};
