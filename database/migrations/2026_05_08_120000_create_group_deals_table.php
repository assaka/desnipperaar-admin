<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_deals', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('city', 120);
            $table->date('pickup_date');

            // Resolved after the first participant row is inserted (organizer).
            $table->unsignedBigInteger('organizer_participant_id')->nullable();

            $table->enum('status', [
                'draft', 'open', 'closed', 'completed', 'cancelled', 'rejected',
            ])->default('draft');

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('pickup_date');
            // One-deal-per-city-per-day rule, ignoring rejected/cancelled deals via app-side check.
            $table->unique(['city', 'pickup_date'], 'group_deals_city_pickup_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_deals');
    }
};
