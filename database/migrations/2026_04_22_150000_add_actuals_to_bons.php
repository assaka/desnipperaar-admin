<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bons', function (Blueprint $table) {
            $table->unsignedInteger('actual_boxes')->nullable()->after('mode');
            $table->unsignedInteger('actual_containers')->nullable()->after('actual_boxes');
            $table->jsonb('actual_media')->nullable()->after('actual_containers');
        });
    }

    public function down(): void
    {
        Schema::table('bons', function (Blueprint $table) {
            $table->dropColumn(['actual_boxes', 'actual_containers', 'actual_media']);
        });
    }
};
