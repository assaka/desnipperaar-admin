<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Pickup ("ophaal") pricing. Regio Amsterdam (<=20 km) is always free.
            // Further away the customer chooses free pickup (min. 2 weeks, we pool the
            // route) or a "sooner" pickup charged at EUR 0,65/km beyond the first 20 km.
            // pickup_cost is the amount actually charged (0 for the free choice); it is
            // added on top of the item subtotal and is subject to 21% BTW.
            $table->decimal('pickup_cost', 10, 2)->default(0)->after('first_box_free');
            $table->unsignedInteger('pickup_km')->nullable()->after('pickup_cost');
            $table->string('pickup_choice', 40)->nullable()->after('pickup_km');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_cost', 'pickup_km', 'pickup_choice']);
        });
    }
};
