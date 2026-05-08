<?php

namespace App\Support;

/**
 * Looks up the woonplaats (city) for a Dutch 4-digit postcode prefix from a
 * curated bundled dataset (resources/data/nl-postcode-cities.json). Permissive:
 * unknown prefixes return null and the validator should treat that as
 * "pass-through" rather than reject — admin still reviews drafts.
 */
class PostcodeLookup
{
    /** @var array<int, array{from:int,to:int,city:string,aliases?:array<int,string>}>|null */
    private static ?array $ranges = null;

    /** @return ?string Canonical city name for the postcode, or null if unknown. */
    public static function cityFor(?string $postcode): ?string
    {
        $prefix = self::extractPrefix($postcode);
        if ($prefix === null) return null;

        foreach (self::ranges() as $r) {
            if ($prefix >= $r['from'] && $prefix <= $r['to']) {
                return $r['city'];
            }
        }
        return null;
    }

    /** Lower-cased variants of the canonical city name (canonical + aliases). */
    public static function canonicalsFor(?string $postcode): array
    {
        $prefix = self::extractPrefix($postcode);
        if ($prefix === null) return [];

        foreach (self::ranges() as $r) {
            if ($prefix >= $r['from'] && $prefix <= $r['to']) {
                $names = array_merge([$r['city']], $r['aliases'] ?? []);
                return array_map([self::class, 'normalize'], $names);
            }
        }
        return [];
    }

    /**
     * Returns true if the postcode and city are compatible. Permissive:
     * if the postcode is unknown to the dataset, returns true (don't reject).
     */
    public static function matches(?string $postcode, ?string $city): bool
    {
        $canonicals = self::canonicalsFor($postcode);
        if (empty($canonicals)) return true; // unknown prefix → pass-through

        $cityNorm = self::normalize((string) $city);
        if ($cityNorm === '') return false; // postcode known, city blank → mismatch

        return in_array($cityNorm, $canonicals, true);
    }

    /** Returns the first canonical city name (for error messages) or null. */
    public static function suggestedCityFor(?string $postcode): ?string
    {
        $prefix = self::extractPrefix($postcode);
        if ($prefix === null) return null;

        foreach (self::ranges() as $r) {
            if ($prefix >= $r['from'] && $prefix <= $r['to']) {
                return $r['city'];
            }
        }
        return null;
    }

    private static function extractPrefix(?string $postcode): ?int
    {
        if (!$postcode) return null;
        $trimmed = preg_replace('/\s+/', '', $postcode);
        if (!preg_match('/^(\d{4})/', $trimmed, $m)) return null;
        return (int) $m[1];
    }

    /** Lowercase, strip diacritics, strip "den"/"'s-"/"-" prefix-noise. */
    private static function normalize(string $name): string
    {
        $s = mb_strtolower(trim($name));
        // Strip diacritics (NFD then drop combining marks).
        if (class_exists(\Normalizer::class)) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_D);
            $s = preg_replace('/\p{Mn}+/u', '', $s);
        }
        // Common Dutch place-name noise: leading "'s-", "den", trailing parenthetical
        // disambiguators ("(gld)" etc.), hyphens collapsed to spaces.
        $s = preg_replace("/^['s]-/u", '', $s);
        $s = preg_replace('/^den\s+/u', '', $s);
        $s = preg_replace('/\s*\([^)]*\)\s*/u', '', $s);
        $s = str_replace('-', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    /** @return array<int, array{from:int,to:int,city:string,aliases?:array<int,string>}> */
    private static function ranges(): array
    {
        if (self::$ranges !== null) return self::$ranges;
        $path = resource_path('data/nl-postcode-cities.json');
        $raw = json_decode((string) file_get_contents($path), true);
        self::$ranges = $raw['ranges'] ?? [];
        return self::$ranges;
    }
}
