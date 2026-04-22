<?php

namespace App\Http\Controllers;

use App\Models\Bon;
use App\Models\Driver;
use App\Models\Seal;
use Illuminate\Http\Request;

class BonController extends Controller
{
    public function show(Bon $bon)
    {
        $bon->load(['order.customer', 'driver', 'seals']);
        $drivers = Driver::active()->orderBy('name')->get(['id','name','license_last4']);
        return view('bons.show', compact('bon', 'drivers'));
    }

    public function update(Request $request, Bon $bon)
    {
        $data = $request->validate([
            'driver_id'    => 'nullable|exists:drivers,id',
            'picked_up_at' => 'nullable|date',
            'weight_kg'    => 'nullable|numeric|min:0|max:10000',
            'notes'        => 'nullable|string|max:5000',
            'seals'        => 'nullable|string|max:5000',
        ]);

        $patch = [
            'picked_up_at' => $data['picked_up_at'] ?? $bon->picked_up_at,
            'weight_kg'    => $data['weight_kg']    ?? $bon->weight_kg,
            'notes'        => $data['notes']        ?? $bon->notes,
        ];

        if (!empty($data['driver_id']) && (int) $data['driver_id'] !== (int) $bon->driver_id) {
            $driver = Driver::find($data['driver_id']);
            $patch['driver_id']            = $driver->id;
            $patch['driver_name_snapshot'] = $driver->name;
            $patch['driver_license_last4'] = $driver->license_last4;
        }

        $bon->update($patch);

        // Seals: one per line. Replace existing.
        if (array_key_exists('seals', $data)) {
            $bon->seals()->delete();
            $lines = array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string) $data['seals'])));
            foreach ($lines as $sealNumber) {
                try {
                    Seal::create([
                        'bon_id'         => $bon->id,
                        'seal_number'    => $sealNumber,
                        'container_type' => 'doos',
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Duplicate seal_number across system — flag but continue.
                    session()->flash('warning', "Zegel {$sealNumber} bestaat al elders.");
                }
            }
        }

        // If picked_up_at just got set, advance order state.
        if (!empty($data['picked_up_at']) && $bon->order->state === \App\Models\Order::STATE_BEVESTIGD) {
            $bon->order->update(['state' => \App\Models\Order::STATE_OPGEHAALD]);
        }
        if (!empty($data['picked_up_at']) && $bon->order->state === \App\Models\Order::STATE_NIEUW) {
            $bon->order->update(['state' => \App\Models\Order::STATE_OPGEHAALD]);
        }

        return redirect()->route('bons.show', $bon);
    }

    public function pdf(Bon $bon)
    {
        $bon->load(['order.customer', 'driver', 'seals']);
        return view('bons.pdf', compact('bon'));
    }
}
