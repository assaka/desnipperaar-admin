<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->timestamp('destroyed_at')->nullable();
            $table->decimal('weight_kg_final', 8, 2)->nullable();
            $table->string('destruction_method')->default('DIN 66399 H-4');
            $table->string('operator_name')->nullable();
            $table->string('operator_signature_path')->nullable();

            $table->string('pdf_path')->nullable();
            $table->timestamp('emailed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
