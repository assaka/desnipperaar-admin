<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->unique();
            $table->timestamp('reschedule_requested_at')->nullable();
            $table->date('reschedule_requested_date')->nullable();
            $table->enum('reschedule_requested_window', ['ochtend', 'middag', 'avond', 'flexibel'])->nullable();
            $table->text('reschedule_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['public_token', 'reschedule_requested_at', 'reschedule_requested_date', 'reschedule_requested_window', 'reschedule_notes']);
        });
    }
};
