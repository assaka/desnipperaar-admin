<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function validate(Request $request): JsonResponse
    {
        $code     = strtoupper(trim($request->query('code', '')));
        $subtotal = (float) $request->query('subtotal', 0);

        if (! $code) {
            return response()->json(['valid' => false, 'error' => 'Code is verplicht.'], 400);
        }

        $coupon = Coupon::findByCode($code);

        if (! $coupon) {
            return response()->json(['valid' => false, 'error' => 'Ongeldige kortingscode.']);
        }

        if (! $coupon->isValid($subtotal)) {
            if ($coupon->expires_at && $coupon->expires_at->isPast()) {
                return response()->json(['valid' => false, 'error' => 'Deze kortingscode is verlopen.']);
            }
            if ($coupon->max_uses !== null && $coupon->times_used >= $coupon->max_uses) {
                return response()->json(['valid' => false, 'error' => 'Deze kortingscode is niet meer geldig.']);
            }
            if ($coupon->min_order_amount !== null && $subtotal < (float) $coupon->min_order_amount) {
                $min = number_format($coupon->min_order_amount, 2, ',', '.');
                return response()->json(['valid' => false, 'error' => "Minimale bestelling van \u{20AC} {$min} vereist voor deze code."]);
            }
            return response()->json(['valid' => false, 'error' => 'Ongeldige kortingscode.']);
        }

        return response()->json([
            'valid'           => true,
            'type'            => $coupon->type,
            'value'           => (float) $coupon->value,
            'discount_amount' => $coupon->discountFor($subtotal),
        ]);
    }

    /**
     * Prefix, percentage and lifetime of the coupon the order page hands out.
     *
     * 10 en niet 25: iemand met een gevulde wagen voor zich heeft minder duwtje
     * nodig dan iemand die een blog las, en de aflopende klok doet hier het werk
     * dat de hoogte van de korting anders zou moeten doen. Zo houdt WELKOM25 op
     * de contentpagina's zijn plek en blijft de SnipperDag met 35 procent een
     * echte reden om zich aan te melden.
     *
     * Codes die al zijn uitgegeven houden hun eigen percentage, want dat staat
     * per rij opgeslagen. Wat is toegezegd blijft staan.
     */
    private const ISSUE_PREFIX = 'SNIP24';
    private const ISSUE_PCT    = 10;
    private const ISSUE_HOURS  = 24;

    /**
     * POST /api/coupon/issue
     *
     * Mints the personal 25% code the order page counts down from. One code per
     * visitor per 24 hours: a repeat call from the same IP gets the same row
     * back with its original expiry, so refreshing the page cannot buy more
     * time than the banner promises.
     *
     * Returns { issued: true, code, pct, expires_at } or { issued: false } when
     * the caller looks like a crawler, which the order page treats as "no offer"
     * rather than as an error.
     */
    public function issue(Request $request): JsonResponse
    {
        // Crawlers that run JavaScript would otherwise leave a coupon row per
        // visit. They never order, so there is nothing to give them.
        $agent = (string) $request->userAgent();
        if ($agent === '' || preg_match('/bot|crawl|spider|slurp|headless|lighthouse|preview|monitor/i', $agent)) {
            return response()->json(['issued' => false]);
        }

        // HMAC rather than the address: enough to recognise the same visitor,
        // without the coupons table becoming a log of who visited.
        $hash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));

        $coupon = Coupon::where('issued_ip_hash', $hash)
            ->where('code', 'LIKE', self::ISSUE_PREFIX . '%')
            ->where('is_active', true)
            ->where('times_used', 0)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $coupon) {
            $coupon = Coupon::create([
                'code'           => $this->freshIssueCode(),
                'type'           => 'percentage',
                'value'          => self::ISSUE_PCT,
                'max_uses'       => 1,
                'expires_at'     => now()->addHours(self::ISSUE_HOURS),
                'is_active'      => true,
                'description'    => 'Automatisch uitgegeven op de bestelpagina',
                'issued_ip_hash' => $hash,
            ]);
        }

        return response()->json([
            'issued'     => true,
            'code'       => $coupon->code,
            'pct'        => (int) round((float) $coupon->value),
            'expires_at' => $coupon->expires_at->toIso8601String(),
        ]);
    }

    /** A unique SNIP24<random> code. Alphabet skips easily-confused characters. */
    private function freshIssueCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $suffix = '';
            for ($i = 0; $i < 5; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = self::ISSUE_PREFIX . $suffix;
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}
