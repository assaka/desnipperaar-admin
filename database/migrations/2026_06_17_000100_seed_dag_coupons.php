<?php

use App\Models\Coupon;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Seed the two DeSnipperaar Dag coupons the public site relies on:
     *   WELKOM25 — instant 25% from the exit-intent popup (always active).
     *   DSDAG35  — the weekly 35% day; created inactive so the code only works
     *              once an admin toggles it active for that day in Coupons.
     */
    public function up(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'WELKOM25'],
            ['type' => 'percentage', 'value' => 25, 'is_active' => true,
             'description' => 'Exit-intent welkomstkorting'],
        );

        Coupon::updateOrCreate(
            ['code' => 'DSDAG35'],
            ['type' => 'percentage', 'value' => 35, 'is_active' => false,
             'description' => 'DeSnipperaar Dag (activeer op de dag zelf)'],
        );
    }

    public function down(): void
    {
        Coupon::whereIn('code', ['WELKOM25', 'DSDAG35'])->delete();
    }
};
