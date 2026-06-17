<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per fired SnipperDag. The unique date is the idempotency
     * guard that stops the daily command from announcing twice on the same day.
     */
    public function up(): void
    {
        Schema::create('dag_announcements', function (Blueprint $table) {
            $table->id();
            $table->date('announced_on')->unique();
            $table->unsignedInteger('recipients')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dag_announcements');
    }
};
