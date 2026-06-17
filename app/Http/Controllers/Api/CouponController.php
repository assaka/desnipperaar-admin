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
}
