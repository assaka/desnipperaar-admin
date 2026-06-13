<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Optional note from the admin to the customer, shown in the pickup
// confirmation e-mail (e.g. explaining why the requested day was unavailable).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->text('pickup_note')->nullable()->after('pickup_window');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('pickup_note'));
    }
};
