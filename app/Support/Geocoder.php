<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Postcode naar coördinaat, via PDOK Locatieserver. Zelfde bron als
 * PostcodeLookup en als api/distance.js op de publieke site, alleen vragen wij
 * hier het middelpunt op in plaats van de woonplaatsnaam.
 *
 * Wij geocoderen op postcode en niet op het volledige adres. Dat scheelt een
 * hoop mislukte lookups op afwijkend geschreven straatnamen, en voor het
 * clusteren van ritten is een paar honderd meter geen verschil: of twee stops
 * bij elkaar in de buurt liggen beslis je op kilometers.
 *
 * Mislukken mag. Een order zonder coördinaat verdwijnt niet uit de planning, hij
 * telt alleen niet mee bij het bepalen of wij ergens toch al langsrijden.
 */
class Geocoder
{
    private const PDOK_URL = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/free';
    private const TTL_HIT  = 86400 * 30;  // 30 dagen; een postcode verhuist niet
    private const TTL_MISS = 3600;        // 1 uur, voor het geval PDOK even stil is
    private const TIMEOUT_S = 4;

    private const EARTH_KM = 6371.0;

    /**
     * Het middelpunt van een postcode, of null als PDOK hem niet kent.
     *
     * @return array{lat: float, lon: float}|null
     */
    public static function forPostcode(?string $postcode): ?array
    {
        $pc = self::normalize($postcode);
        if ($pc === null) {
            return null;
        }

        $key = "pdok:point:{$pc}";
        if (Cache::has($key)) {
            return Cache::get($key);
        }

        $point = self::fetch($pc);
        Cache::put($key, $point, $point ? self::TTL_HIT : self::TTL_MISS);

        return $point;
    }

    /**
     * Het punt van een order, opgezocht en bewaard zodra dat nodig is.
     *
     * De kolommen worden stil bijgewerkt: dit is afgeleide data over een adres
     * dat niet verandert, geen wijziging waar iemand een mail over hoort te
     * krijgen. geocoded_at wordt ook gezet als er niets gevonden is, zodat een
     * onvindbare postcode niet bij elke planning opnieuw wordt opgevraagd.
     *
     * @return array{lat: float, lon: float}|null
     */
    public static function forOrder(Order $order): ?array
    {
        if ($order->lat !== null && $order->lon !== null) {
            return ['lat' => (float) $order->lat, 'lon' => (float) $order->lon];
        }

        // Al eens geprobeerd en niets gevonden. Pas opnieuw proberen als het
        // adres is gewijzigd, en dan wist de orderpagina geocoded_at.
        if ($order->geocoded_at !== null) {
            return null;
        }

        $point = self::forPostcode($order->customer_postcode);

        $order->lat = $point['lat'] ?? null;
        $order->lon = $point['lon'] ?? null;
        $order->geocoded_at = now();
        $order->saveQuietly();

        return $point;
    }

    /** Hemelsbrede afstand in kilometers tussen twee punten. */
    public static function haversineKm(array $a, array $b): float
    {
        $lat1 = deg2rad((float) $a['lat']);
        $lat2 = deg2rad((float) $b['lat']);
        $dLat = $lat2 - $lat1;
        $dLon = deg2rad((float) $b['lon'] - (float) $a['lon']);

        $h = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;

        return self::EARTH_KM * 2 * atan2(sqrt($h), sqrt(1 - $h));
    }

    /**
     * Benaderde wegafstand. Wij vragen geen routeservice om een getal dat alleen
     * bepaalt of twee stops "in de buurt" liggen; hemelsbreed maal een vaste
     * factor is daarvoor nauwkeurig genoeg en kost geen quotum. De echte
     * wegafstand voor de prijs komt van /api/distance op de publieke site en
     * staat als pickup_km op de order.
     */
    public static function roadKm(array $a, array $b): float
    {
        return self::haversineKm($a, $b) * (float) config('desnipperaar.planning.road_factor', 1.3);
    }

    /** @return array{lat: float, lon: float} */
    public static function depot(): array
    {
        return [
            'lat' => (float) config('desnipperaar.planning.depot.lat'),
            'lon' => (float) config('desnipperaar.planning.depot.lon'),
        ];
    }

    /**
     * @return array{lat: float, lon: float}|null
     */
    private static function fetch(string $postcode): ?array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_S)
                ->acceptJson()
                ->get(self::PDOK_URL, [
                    'q'    => $postcode,
                    'fq'   => 'type:postcode',
                    'fl'   => 'centroide_ll',
                    'rows' => 1,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $wkt = $response->json('response.docs.0.centroide_ll');
            if (! is_string($wkt) || ! preg_match('/POINT\(([-\d.]+)\s+([-\d.]+)\)/', $wkt, $m)) {
                return null;
            }

            // WKT is POINT(lon lat), in die volgorde.
            return ['lon' => (float) $m[1], 'lat' => (float) $m[2]];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** "1034 dn" wordt "1034DN"; alles wat geen Nederlandse postcode is wordt null. */
    private static function normalize(?string $postcode): ?string
    {
        if (! $postcode) {
            return null;
        }

        $pc = strtoupper(preg_replace('/\s+/', '', $postcode));

        return preg_match('/^\d{4}[A-Z]{2}$/', $pc) ? $pc : null;
    }
}
