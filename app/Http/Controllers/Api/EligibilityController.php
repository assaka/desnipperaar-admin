<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class EligibilityController extends Controller
{
    /** Returns kennismaking (first-box-free) eligibility for an email address. */
    public function kennismaking(Request $request)
    {
        $email = strtolower(trim($request->query('email', '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['eligible' => null, 'reason' => 'no_email']);
        }

        $customer = Customer::whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$customer) {
            return response()->json(['eligible' => true, 'reason' => 'new_customer']);
        }
        if ($customer->orders()->doesntExist()) {
            return response()->json(['eligible' => true, 'reason' => 'customer_no_orders_yet']);
        }

        return response()->json([
            'eligible' => false,
            'reason'   => 'customer_has_previous_orders',
            'previous_order_count' => $customer->orders()->count(),
        ]);
    }
}
