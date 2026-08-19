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

    /**
     * Zelfde rekensom als het winkelwagentje op /order: dozen, rolcontainers én
     * datadragers met hun volumestaffel in één overzicht.
     *
     * Naast quote() en niet in plaats daarvan. Die geeft alleen dozen en
     * containers terug, en het orderformulier rekent daarmee. snapshot() voegt de
     * dragers toe, wat een offerte op maat nodig heeft.
     */
    public function snapshot(Request $request)
    {
        $data = $request->validate([
            'boxes'          => 'nullable|integer|min:0|max:5000',
            'containers'     => 'nullable|integer|min:0|max:500',
            'first_box_free' => 'nullable|boolean',
            'media'          => 'nullable|array:' . implode(',', array_keys(Pricing::MEDIA_TIERS)),
            'media.*'        => 'nullable|integer|min:0|max:100000',
        ]);

        // pilot is obsoleet en staat hier hard op false: een nieuwe offerte
        // rekent nooit meer met de pilottarieven.
        return response()->json(Pricing::snapshot(
            (int) ($data['boxes']      ?? 0),
            (int) ($data['containers'] ?? 0),
            array_map('intval', $data['media'] ?? []),
            false,
            (bool) ($data['first_box_free'] ?? false),
        ));
    }
}
