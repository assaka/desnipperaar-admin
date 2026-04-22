<?php

namespace App\Http\Controllers;

use App\Mail\BonSigned;
use App\Models\Bon;
use App\Models\Driver;
use App\Models\Seal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
            'driver_id'           => 'nullable|exists:drivers,id',
            'picked_up_at'        => 'nullable|date',
            'weight_kg'           => 'nullable|numeric|min:0|max:10000',
            'notes'               => 'nullable|string|max:5000',
            'seals'               => 'nullable|string|max:5000',
            'customer_signature'  => 'nullable|string',
            'driver_signature'    => 'nullable|string',
        ]);

        $hadSignatureBefore = !empty($bon->customer_signature_path);
        $hadPickupBefore    = !empty($bon->picked_up_at);

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

        if (!empty($data['customer_signature']) && str_starts_with($data['customer_signature'], 'data:image/')) {
            $patch['customer_signature_path'] = $this->storeSignature($bon, 'customer', $data['customer_signature']);
        }
        if (!empty($data['driver_signature']) && str_starts_with($data['driver_signature'], 'data:image/')) {
            $patch['driver_signature_path'] = $this->storeSignature($bon, 'driver', $data['driver_signature']);
        }

        $bon->update($patch);

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
                    session()->flash('warning', "Zegel {$sealNumber} bestaat al elders.");
                }
            }
        }

        // Advance order state when pickup is finalized.
        if (!empty($data['picked_up_at']) && in_array($bon->order->state, [\App\Models\Order::STATE_NIEUW, \App\Models\Order::STATE_BEVESTIGD])) {
            $bon->order->update(['state' => \App\Models\Order::STATE_OPGEHAALD]);
        }

        // Mail signed bon to customer — only once, when signature + pickup are both present.
        $hasSignatureNow = !empty($bon->fresh()->customer_signature_path);
        $hasPickupNow    = !empty($bon->fresh()->picked_up_at);
        $justSigned      = $hasSignatureNow && $hasPickupNow && (!$hadSignatureBefore || !$hadPickupBefore);

        if ($justSigned) {
            try {
                Mail::to($bon->order->customer_email)
                    ->send(new BonSigned($bon->fresh()->load(['order.customer', 'driver', 'seals']), $request->user()));
                session()->flash('status', "Getekende bon per e-mail verstuurd naar {$bon->order->customer_email}.");
            } catch (\Throwable $e) {
                report($e);
                session()->flash('warning', 'Bon opgeslagen maar mail kon niet worden verstuurd: ' . $e->getMessage());
            }
        }

        return redirect()->route('bons.show', $bon);
    }

    public function pdf(Bon $bon)
    {
        $bon->load(['order.customer', 'driver', 'seals']);
        return view('bons.pdf', compact('bon'));
    }

    public function signature(Bon $bon, string $role)
    {
        $path = $role === 'customer' ? $bon->customer_signature_path : $bon->driver_signature_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return response(Storage::disk('local')->get($path), 200, ['Content-Type' => 'image/png']);
    }

    private function storeSignature(Bon $bon, string $role, string $dataUri): string
    {
        $base64 = preg_replace('#^data:image/\w+;base64,#', '', $dataUri);
        $binary = base64_decode($base64);
        $path   = "signatures/bon-{$bon->id}-{$role}.png";
        Storage::disk('local')->put($path, $binary);
        return $path;
    }
}
