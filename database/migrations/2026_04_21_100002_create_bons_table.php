<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bons', function (Blueprint $table) {
            $table->id();
            $table->string('bon_number')->unique();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers');

            // Frozen audit snapshot — survives driver record edits.
            $table->string('driver_name_snapshot')->nullable();
            $table->char('driver_license_last4', 4)->nullable();

            $table->enum('mode', ['ophaal', 'breng', 'mobiel']);
            $table->timestamp('picked_up_at')->nullable();
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->text('notes')->nullable();

            $table->string('customer_signature_path')->nullable();
            $table->string('driver_signature_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bons');
    }
};
