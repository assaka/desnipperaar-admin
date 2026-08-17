<?php

use App\Models\Coupon;
use Illuminate\Database\Migrations\Migration;

/**
 * WELKOM10 ingetrokken.
 *
 * De bestelpagina geeft sinds kort een persoonlijke code van 10 procent uit die
 * per bezoeker wordt gemunt en na 24 uur verloopt (SNIP24, zie CouponController
 * en assets/order-deal.js). WELKOM10 gaf hetzelfde percentage, maar dan als
 * vaste code zonder houdbaarheid, en hij stond nergens meer in de front-end.
 * Laten staan betekent een code die eeuwig geldig blijft zodra iemand hem ooit
 * ergens heeft zien staan.
 *
 * Deactiveren en niet verwijderen: orders die er ooit mee zijn geplaatst
 * verwijzen naar deze code, en die moeten achteraf uit te leggen blijven.
 */
return new class extends Migration
{
    public function up(): void
    {
        Coupon::where('code', 'WELKOM10')->update(['is_active' => false]);
    }

    public function down(): void
    {
        Coupon::where('code', 'WELKOM10')->update(['is_active' => true]);
    }
};
