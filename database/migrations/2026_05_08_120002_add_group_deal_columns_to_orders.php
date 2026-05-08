<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('group_deal_id')
                ->nullable()
                ->after('id')
                ->constrained('group_deals')
                ->nullOnDelete();

            $table->boolean('is_organizer')->default(false)->after('group_deal_id');

            // When true, OrderCreated email + invoice consume price_snapshot directly
            // instead of calling Pricing::quote() — locks the price at deal-join time.
            $table->boolean('quote_locked')->default(false)->after('is_organizer');
            $table->jsonb('price_snapshot')->nullable()->after('quote_locked');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['group_deal_id']);
            $table->dropColumn(['group_deal_id', 'is_organizer', 'quote_locked', 'price_snapshot']);
        });
    }
};
