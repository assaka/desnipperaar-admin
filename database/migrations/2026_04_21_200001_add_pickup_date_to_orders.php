<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('pickup_date')->nullable()->after('container_count');
            $table->enum('pickup_window', ['ochtend', 'middag', 'avond', 'flexibel'])->nullable()->after('pickup_date');
            $table->boolean('first_box_free')->default(false)->after('pilot');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_date', 'pickup_window', 'first_box_free']);
        });
    }
};
