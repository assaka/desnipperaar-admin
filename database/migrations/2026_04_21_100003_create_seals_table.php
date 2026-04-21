<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bon_id')->constrained()->cascadeOnDelete();
            $table->string('seal_number')->unique();
            $table->enum('container_type', ['doos', 'rolcontainer_240', 'rolcontainer_660'])->default('doos');
            $table->timestamp('closed_at_destruction')->nullable();
            $table->text('destruction_note')->nullable();
            $table->timestamps();

            $table->index('bon_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seals');
    }
};
