<?php

use App\Models\Coupon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;

/**
 * Per 1 november 2026 geeft de pop-up op de contentpagina's 15% (WELKOM15) in
 * plaats van 25% (WELKOM25). De site wisselt de code zelf op die datum
 * (site-config.json discount.next). WELKOM15 bestaat vanaf nu al, zodat hij
 * werkt zodra de site hem toont. WELKOM25 verloopt eind 31 oktober.
 *
 * Verlopen en niet verwijderen: orders die er ooit mee zijn geplaatst
 * verwijzen naar deze code, en die moeten achteraf uit te leggen blijven.
 */
return new class extends Migration
{
    public function up(): void
    {
        Coupon::firstOrCreate(['code' => 'WELKOM15'], [
            'type'        => 'percentage',
            'value'       => 15,
            'is_active'   => true,
            'description' => "Exit-intent pop-up op contentpagina's, vanaf 1 november 2026",
        ]);

        Coupon::where('code', 'WELKOM25')->update([
            'expires_at' => Carbon::parse('2026-10-31 23:59:59', 'Europe/Amsterdam'),
        ]);
    }

    public function down(): void
    {
        Coupon::where('code', 'WELKOM15')->delete();
        Coupon::where('code', 'WELKOM25')->update(['expires_at' => null]);
    }
};
