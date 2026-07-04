<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Itemised quote rows. Same shape as invoices.lines:
            // [{label, qty, unit, subtotal}]. When present, quoted_amount_excl_btw
            // is the sum of the subtotals.
            $table->json('quote_lines')->nullable()->after('quote_body');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('quote_lines');
        });
    }
};
