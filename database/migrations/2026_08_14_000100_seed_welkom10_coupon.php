<?php

use App\Models\Coupon;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * WELKOM10 — the exit-intent popup on the order and contact page.
     *
     * The popup elsewhere on the site offers WELKOM25. On /order and /contact
     * the visitor is already halfway in, so the same popup hands out a smaller
     * 10% code there. Without this row /api/coupon rejects the code and the
     * popup promises a discount the order form refuses.
     */
    public function up(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'WELKOM10'],
            ['type' => 'percentage', 'value' => 10, 'is_active' => true,
             'description' => 'Exit-intent korting bestel- en contactpagina'],
        );
    }

    public function down(): void
    {
        Coupon::where('code', 'WELKOM10')->delete();
    }
};
