<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_deals', function (Blueprint $table) {
            // Aspirational target the deal is working toward — informational, not
            // a hard activation gate. The deal still closes at T-N days regardless
            // of whether the target was met; it just shows progress on the public
            // detail page so joiners see how close the group is.
            $table->unsignedInteger('target_box_count')->default(0)->after('pickup_date');
            $table->unsignedInteger('target_container_count')->default(0)->after('target_box_count');
        });
    }

    public function down(): void
    {
        Schema::table('group_deals', function (Blueprint $table) {
            $table->dropColumn(['target_box_count', 'target_container_count']);
        });
    }
};
