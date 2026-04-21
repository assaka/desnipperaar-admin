<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OfferteController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email',
            'phone'       => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:255',
            'postcode'    => 'nullable|string|max:10',
            'city'        => 'nullable|string|max:100',
            'reference'   => 'nullable|string|max:100',
            'mode'        => 'nullable|in:ophaal,breng,mobiel',
            'boxes'       => 'nullable|integer|min:0',
            'containers'  => 'nullable|integer|min:0',
            'media'       => 'nullable|array',
            'notes'       => 'nullable|string|max:2000',
        ]);

        $postcode = preg_replace('/\s+/', '', $data['postcode'] ?? '');
        $numeric  = (int) substr($postcode, 0, 4);
        $pilot    = $numeric >= config('desnipperaar.pilot.postcode_start')
                 && $numeric <= config('desnipperaar.pilot.postcode_end');

        $order = Order::create([
            'order_number'       => Order::generateOrderNumber(),
            'customer_name'      => $data['name'],
            'customer_email'     => $data['email'],
            'customer_phone'     => $data['phone']     ?? null,
            'customer_address'   => $data['address']   ?? null,
            'customer_postcode'  => $postcode ?: null,
            'customer_city'      => $data['city']      ?? null,
            'customer_reference' => $data['reference'] ?? null,
            'delivery_mode'      => $data['mode']      ?? 'ophaal',
            'box_count'          => $data['boxes']      ?? 0,
            'container_count'    => $data['containers'] ?? 0,
            'media_items'        => $data['media']     ?? null,
            'notes'              => $data['notes']     ?? null,
            'state'              => Order::STATE_NIEUW,
            'pilot'              => $pilot,
        ]);

        // TODO: dispatch SendOrderConfirmation job

        return response()->json([
            'ok' => true,
            'order_number' => $order->order_number,
        ], 201);
    }
}
