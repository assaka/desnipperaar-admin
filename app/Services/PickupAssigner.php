<?php

namespace App\Services;

use App\Models\Bon;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;

/**
 * Een chauffeur aan een ophaling hangen, met de bon die daarbij hoort.
 *
 * Stond eerst alleen in confirmPickup op de orderpagina. Sinds de klant zijn
 * moment zelf kiest gebeurt hetzelfde vanaf twee plekken, en twee keer dezelfde
 * bon aanmaken is twee keer dezelfde fout maken.
 */
class PickupAssigner
{
    /**
     * De chauffeur die wij vanzelf toewijzen, of null als er iets te kiezen valt.
     *
     * Rijdt er maar één, dan is er geen keuze en hoeft niemand hem te maken.
     * Zodra er een tweede bij komt geeft dit null terug en blijft de ophaling
     * wachten op een mens, want dan is het gokken. Zelfde regel als het lijstje
     * op de orderpagina, zodat scherm en automaat niet uit elkaar lopen.
     */
    public static function soleDriver(): ?Driver
    {
        $drivers = Driver::active()->orderBy('name')->get();

        return $drivers->count() === 1 ? $drivers->first() : null;
    }

    /**
     * Zet de chauffeur op de bon van deze order en maak de bon aan als hij nog
     * niet bestaat. Geeft de bon terug.
     *
     * De handtekening van de chauffeur wordt gekopieerd en niet gedeeld: de bon
     * moet blijven tonen wie er die dag tekende, ook als de chauffeur later een
     * nieuwe handtekening opslaat.
     */
    public static function attach(Order $order, Driver $driver): Bon
    {
        $bon = $order->bons()->orderBy('id')->first();

        if (! $bon) {
            $bon = Bon::create([
                'bon_number' => Bon::generateBonNumber(),
                'order_id'   => $order->id,
                'mode'       => $order->delivery_mode,
            ]);
        }

        $patch = [
            'driver_id'            => $driver->id,
            'driver_name_snapshot' => $driver->name,
            'driver_license_last4' => $driver->license_last4,
        ];

        if ($driver->signature_path && empty($bon->driver_signature_path)) {
            $copy = "signatures/bon-{$bon->id}-driver.png";
            Storage::disk('local')->put($copy, Storage::disk('local')->get($driver->signature_path));
            $patch['driver_signature_path'] = $copy;
        }

        $bon->update($patch);

        return $bon;
    }
}
