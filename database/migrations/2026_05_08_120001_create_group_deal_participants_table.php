<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_deal_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_deal_id')
                ->constrained('group_deals')
                ->cascadeOnDelete();

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->string('customer_postcode', 10);
            $table->string('customer_address');
            $table->string('customer_city')->nullable();

            $table->unsignedInteger('box_count')->default(0);
            $table->unsignedInteger('container_count')->default(0);
            $table->jsonb('media_items')->nullable();
            $table->text('notes')->nullable();

            // Locked at join time. Mirrors Pricing::quote() shape with media lines folded in.
            $table->jsonb('price_snapshot');

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index('group_deal_id');
            $table->index('customer_email');
        });

        // Resolve the cyclic FK from group_deals.organizer_participant_id.
        Schema::table('group_deals', function (Blueprint $table) {
            $table->foreign('organizer_participant_id')
                ->references('id')->on('group_deal_participants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('group_deals', function (Blueprint $table) {
            $table->dropForeign(['organizer_participant_id']);
        });
        Schema::dropIfExists('group_deal_participants');
    }
};
