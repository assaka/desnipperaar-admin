<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auto-issued visitor coupons (SNIP24…) need to recognise a returning visitor,
 * otherwise a refresh mints a second code and the 24-hour deadline the order
 * page counts down to is theatre.
 *
 * Stored as an HMAC of the IP under APP_KEY rather than the address itself: it
 * still matches the same visitor, but the coupons table never holds an IP we'd
 * have to account for in the privacy statement. Null for every hand-made coupon.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('issued_ip_hash', 64)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('issued_ip_hash');
        });
    }
};
