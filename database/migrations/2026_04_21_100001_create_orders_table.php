<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();

            // Klant
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->string('customer_address')->nullable();
            $table->string('customer_postcode', 10)->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_reference')->nullable();

            // Order
            $table->enum('delivery_mode', ['ophaal', 'breng', 'mobiel'])->default('ophaal');
            $table->unsignedInteger('box_count')->default(0);
            $table->unsignedInteger('container_count')->default(0);
            $table->jsonb('media_items')->nullable();
            $table->text('notes')->nullable();

            // State
            $table->enum('state', [
                'nieuw', 'bevestigd', 'opgehaald', 'vernietigd', 'afgesloten'
            ])->default('nieuw');
            $table->boolean('pilot')->default(false);

            $table->timestamps();
            $table->index('state');
            $table->index('customer_postcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
