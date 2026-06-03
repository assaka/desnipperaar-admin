<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponAdminController extends Controller
{
    public function index()
    {
        $coupons = Coupon::orderByDesc('created_at')->get();
        return view('coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('coupons.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['code'] = strtoupper($data['code']);
        Coupon::create($data);
        return redirect()->route('coupons.index')->with('status', "Coupon {$data['code']} aangemaakt.");
    }

    public function edit(Coupon $coupon)
    {
        return view('coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $this->validated($request, $coupon->id);
        $data['code'] = strtoupper($data['code']);
        $coupon->update($data);
        return redirect()->route('coupons.index')->with('status', "Coupon {$coupon->code} bijgewerkt.");
    }

    public function destroy(Coupon $coupon)
    {
        $code = $coupon->code;
        $coupon->delete();
        return redirect()->route('coupons.index')->with('status', "Coupon {$code} verwijderd.");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code'             => 'required|string|max:50|unique:coupons,code' . ($ignoreId ? ",{$ignoreId}" : ''),
            'type'             => 'required|in:percentage,fixed',
            'value'            => 'required|numeric|min:0' . ($request->type === 'percentage' ? '|max:100' : ''),
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses'         => 'nullable|integer|min:1',
            'expires_at'       => 'nullable|date',
            'is_active'        => 'nullable|boolean',
            'description'      => 'nullable|string|max:255',
        ]);
    }
}
