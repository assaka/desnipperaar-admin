<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    public function index()
    {
        $drivers = Driver::orderBy('name')->get();
        return view('drivers.index', compact('drivers'));
    }

    public function store(Request $request)
    {
        $data = $this->validateDriver($request);
        $driver = Driver::create(array_except($data, ['signature']));
        $this->persistSignature($driver, $request);
        return redirect()->route('drivers.index')->with('status', "Chauffeur {$driver->name} aangemaakt.");
    }

    public function edit(Driver $driver)
    {
        return view('drivers.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        $data = $this->validateDriver($request, $driver->id);
        $driver->update(array_except($data, ['signature']));
        $this->persistSignature($driver, $request);
        return redirect()->route('drivers.index')->with('status', "Chauffeur {$driver->name} bijgewerkt.");
    }

    private function validateDriver(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'            => 'required|string|max:255',
            'license_last4'   => 'required|string|size:4',
            'vog_valid_until' => 'nullable|date',
            'active'          => 'nullable|boolean',
            'signature'       => 'nullable|string',
        ]);
    }

    public function signature(Driver $driver)
    {
        abort_unless($driver->signature_path && Storage::disk('local')->exists($driver->signature_path), 404);
        return response(Storage::disk('local')->get($driver->signature_path), 200, ['Content-Type' => 'image/png']);
    }

    private function persistSignature(Driver $driver, Request $request): void
    {
        $sig = $request->input('signature');
        if ($sig && str_starts_with($sig, 'data:image/')) {
            $base64 = preg_replace('#^data:image/\w+;base64,#', '', $sig);
            $binary = base64_decode($base64);
            $path   = "signatures/driver-{$driver->id}.png";
            Storage::disk('local')->put($path, $binary);
            $driver->update(['signature_path' => $path]);
        }
    }
}

// Polyfill helper — small shim since there's no native array_except in vanilla PHP.
if (!function_exists('array_except')) {
    function array_except(array $array, array $keys): array
    {
        foreach ($keys as $k) unset($array[$k]);
        return $array;
    }
}
