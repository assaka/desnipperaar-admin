<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De onderbouwing van de korting, niet alleen de uitkomst.
 *
 * coupon_discount alleen zegt "− € 13,75" en laat de klant uitzoeken waar dat
 * vandaan komt. Met het soort, de waarde en het bedrag waarover gerekend is,
 * kan de factuur "25% × € 55,00" laten zien en klopt de rekensom zichtbaar.
 *
 * Alle drie zijn, net als coupon_discount, een momentopname op het moment van
 * toekennen: gaat WELKOM25 later naar 20%, dan blijft op deze order staan wat
 * de klant destijds is gegeven.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('coupon_type', 20)->nullable();
            $table->decimal('coupon_value', 10, 2)->nullable();
            $table->decimal('coupon_base', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['coupon_type', 'coupon_value', 'coupon_base']);
        });
    }
};
