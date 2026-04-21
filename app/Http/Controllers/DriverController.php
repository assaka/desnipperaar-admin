<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index()
    {
        $drivers = Driver::orderBy('name')->get();
        return view('drivers.index', compact('drivers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'license_last4'   => 'required|string|size:4',
            'vog_valid_until' => 'nullable|date',
            'active'          => 'boolean',
        ]);

        Driver::create($data);
        return back();
    }
}
