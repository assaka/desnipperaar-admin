<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kortingscode op een order die al bestaat.
 *
 * Bij het bestellen rekende de site de korting al in het cartoverzicht en gaf de
 * code alleen mee om times_used op te hogen: de order zelf wist er niets van.
 * Een code die pas na het aanmaken wordt toegekend, omdat de klant hem vergat of
 * er achteraf een belofte is gedaan, had daarmee nergens een plek om te landen.
 *
 * Het bedrag wordt bij het toekennen berekend en blijft daarna staan. Dat is
 * bewust een momentopname: verandert de coupon later van percentage, dan mag een
 * al verstuurde factuur daar niet mee mee bewegen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('coupon_code', 50)->nullable();
            $table->decimal('coupon_discount', 10, 2)->nullable();
            $table->timestamp('coupon_applied_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['coupon_code', 'coupon_discount', 'coupon_applied_at']);
        });
    }
};
