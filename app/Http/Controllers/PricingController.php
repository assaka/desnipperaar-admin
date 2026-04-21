<?php

namespace App\Http\Controllers;

use App\Support\Pricing;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function quote(Request $request)
    {
        $data = $request->validate([
            'boxes'          => 'nullable|integer|min:0|max:500',
            'containers'     => 'nullable|integer|min:0|max:50',
            'pilot'          => 'nullable|boolean',
            'first_box_free' => 'nullable|boolean',
        ]);

        return response()->json(Pricing::quote(
            (int) ($data['boxes']      ?? 0),
            (int) ($data['containers'] ?? 0),
            (bool) ($data['pilot']          ?? false),
            (bool) ($data['first_box_free'] ?? false),
        ));
    }
}
