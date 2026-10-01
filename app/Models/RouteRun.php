<?php

namespace App\Models;

use App\Support\Geocoder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Een verre rit die toch al gereden wordt, met ruimte voor klanten onderweg.
 *
 * De rit is depot -> bestemming -> depot. Een klant past erin als hem
 * ertussen schuiven niet meer dan max_detour_km extra kost:
 *
 *     omweg = afstand(depot, klant) + afstand(klant, bestemming)
 *             - afstand(depot, bestemming)
 *
 * Dezelfde invoegsom als SlotFinder::insertionCost(), alleen tegen deze ene
 * rit. Heen of terug maakt niet uit, de som is symmetrisch. Klanten die al
 * meerijden tellen niet mee in de route: verre ophalingen plannen wij met de
 * hand, en dan beslissen wij zelf of de vijfde stop er nog bij past.
 */
class RouteRun extends Model
{
    protected $fillable = [
        'label',
        'destination_postcode',
        'lat',
        'lon',
        'run_date',
        'max_detour_km',
        'is_open',
        'anchor_order_id',
        'notes',
    ];

    protected $casts = [
        'run_date'      => 'date',
        'max_detour_km' => 'float',
        'is_open'       => 'boolean',
        'lat'           => 'float',
        'lon'           => 'float',
    ];

    public function anchorOrder()
    {
        return $this->belongsTo(Order::class, 'anchor_order_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Open en nog niet gereden. Een rit zonder datum telt als open zolang
     * hij openstaat; een rit met datum vanaf morgen, want voor vandaag kan
     * niemand meer bestellen en erbij komen.
     */
    public function scopeBookable(Builder $q): Builder
    {
        return $q->where('is_open', true)
            ->where(fn (Builder $w) => $w->whereNull('run_date')
                ->orWhere('run_date', '>', now()->toDateString()));
    }

    /** @return array{lat: float, lon: float}|null */
    public function point(): ?array
    {
        if ($this->lat !== null && $this->lon !== null) {
            return ['lat' => $this->lat, 'lon' => $this->lon];
        }

        return null;
    }

    /** Extra kilometers om dit punt in de rit op te nemen, of null zonder coördinaat. */
    public function detourKm(?array $point): ?float
    {
        $dest = $this->point();
        if (! $point || ! $dest) {
            return null;
        }

        $depot = Geocoder::depot();

        return max(0.0,
            Geocoder::roadKm($depot, $point)
            + Geocoder::roadKm($point, $dest)
            - Geocoder::roadKm($depot, $dest)
        );
    }

    /**
     * De rit waar deze postcode op past, met de kleinste omweg. Null als er
     * geen is. Bij gelijke omweg wint de vroegste datum, en een rit zonder
     * datum komt achter een rit met datum.
     */
    public static function matchFor(?string $postcode): ?array
    {
        $point = Geocoder::forPostcode($postcode);
        if (! $point) {
            return null;
        }

        $best = null;
        foreach (self::bookable()->get() as $run) {
            $detour = $run->detourKm($point);
            if ($detour === null || $detour > $run->max_detour_km) {
                continue;
            }

            $key = [$detour, $run->run_date?->toDateString() ?? '9999-12-31'];
            if ($best === null || $key < $best['key']) {
                $best = ['key' => $key, 'run' => $run, 'detour_km' => round($detour, 1)];
            }
        }

        if ($best === null) {
            return null;
        }

        unset($best['key']);

        return $best;
    }
}
