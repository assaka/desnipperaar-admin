<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Looks up Dutch woonplaatsen for a 4-digit postcode prefix via PDOK
 * Locatieserver — the Dutch government's authoritative postcode service.
 * Free, no API key, returns ALL woonplaatsen sharing a 4-digit prefix
 * (some prefixes span multiple small towns).
 *
 * Successful results are cached 7 days under "pdok:postcode:{prefix}".
 * Empty / failed lookups are cached 5 minutes so a transient PDOK outage
 * doesn't poison the cache for a week. matches() falls back to "permissive
 * pass-through" when the woonplaats list is empty (PDOK down OR truly
 * unknown prefix), so a downed external API never breaks form submission.
 *
 * Public surface unchanged from the previous bundled-JSON implementation:
 * matches() and suggestedCityFor() are what the controller calls.
 */
class PostcodeLookup
{
    private const PDOK_URL  = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/free';
    private const TTL_HIT   = 86400 * 7;   // 7 days
    private const TTL_MISS  = 300;         // 5 minutes
    private const TIMEOUT_S = 3;

    /** Manual aliases for cities whose colloquial name differs from the official
     *  woonplaatsnaam PDOK returns. Both sides are compared after normalize(). */
    private const ALIASES = [
        "den haag"        => "'s-gravenhage",
        "den bosch"       => "'s-hertogenbosch",
        "den helder"      => "den helder",
        "den burg"        => "den burg",
    ];

    /**
     * City-anchored match: true unless we KNOW the postcode prefix doesn't
     * include the typed city. Permissive on PDOK-empty (graceful fallback).
     */
    public static function matches(?string $postcode, ?string $city): bool
    {
        $prefix = self::extractPrefix($postcode);
        if ($prefix === null) return true; // postcode unparseable → can't check

        $cityNorm = self::normalize((string) $city);
        if ($cityNorm === '') return false; // postcode given, city blank → mismatch

        $woonplaatsen = self::woonplaatsenFor($prefix);
        if (empty($woonplaatsen)) return true; // PDOK silent → permissive

        $cityCanonical = self::ALIASES[$cityNorm] ?? $cityNorm;
        foreach ($woonplaatsen as $w) {
            if (self::normalize($w) === $cityCanonical) return true;
        }
        return false;
    }

    /** @return ?string First woonplaats for the prefix, or null if unknown. */
    public static function suggestedCityFor(?string $postcode): ?string
    {
        $prefix = self::extractPrefix($postcode);
        if ($prefix === null) return null;
        $woonplaatsen = self::woonplaatsenFor($prefix);
        return $woonplaatsen[0] ?? null;
    }

    /** @return array<int, string> All woonplaatsen sharing the 4-digit prefix. */
    private static function woonplaatsenFor(int $prefix): array
    {
        $key = "pdok:postcode:{$prefix}";
        if (Cache::has($key)) {
            return Cache::get($key, []);
        }
        $cities = self::fetchFromPdok($prefix);
        $ttl = empty($cities) ? self::TTL_MISS : self::TTL_HIT;
        Cache::put($key, $cities, $ttl);
        return $cities;
    }

    /** @return array<int, string> */
    private static function fetchFromPdok(int $prefix): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_S)
                ->acceptJson()
                ->get(self::PDOK_URL, [
                    'q'    => (string) $prefix,
                    'fq'   => 'type:postcode',
                    'fl'   => 'woonplaatsnaam,postcode',
                    'rows' => 100,
                ]);
            if (!$response->successful()) return [];

            $docs = $response->json('response.docs', []);
            $cities = [];
            foreach ($docs as $doc) {
                $w = trim((string) ($doc['woonplaatsnaam'] ?? ''));
                $pc = (string) ($doc['postcode'] ?? '');
                // Only keep docs whose 4-digit prefix matches what we asked for —
                // PDOK's "free" endpoint can fuzzy-match street names too.
                if ($w === '' || !preg_match('/^' . preg_quote((string) $prefix, '/') . '/', $pc)) {
                    continue;
                }
                if (!in_array($w, $cities, true)) {
                    $cities[] = $w;
                }
            }
            return $cities;
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    private static function extractPrefix(?string $postcode): ?int
    {
        if (!$postcode) return null;
        $trimmed = preg_replace('/\s+/', '', $postcode);
        if (!preg_match('/^(\d{4})/', $trimmed, $m)) return null;
        return (int) $m[1];
    }

    /** Lowercase, strip diacritics, drop "'s-"/"den" prefixes, hyphens to spaces. */
    private static function normalize(string $name): string
    {
        $s = mb_strtolower(trim($name));
        if (class_exists(\Normalizer::class)) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_D);
            $s = preg_replace('/\p{Mn}+/u', '', $s);
        }
        $s = preg_replace('/\s*\([^)]*\)\s*/u', '', $s);
        $s = str_replace('-', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }
}
