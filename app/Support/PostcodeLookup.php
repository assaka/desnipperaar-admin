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
     * City-anchored match: if the typed city has any ranges in the dataset,
     * the postcode prefix must fall in one of *those* ranges. If the city is
     * not in the dataset at all, pass through (we can't say it's wrong).
     *
     * This catches the previous false-negative where typing an in-gap postcode
     * (e.g. 1234) plus a known city (e.g. "Haarlem") passed because cityFor(
     * 1234) returned null.
     */
    public static function matches(?string $postcode, ?string $city): bool
    {
        $prefix = self::extractPrefix($postcode);
        if ($prefix === null) return true; // postcode unparseable → can't check

        $cityNorm = self::normalize((string) $city);
        if ($cityNorm === '') return false; // postcode given, city blank → mismatch

        $matching = [];
        foreach (self::ranges() as $r) {
            $names = self::namesFor($r);
            if (in_array($cityNorm, $names, true)) {
                $matching[] = $r;
            }
        }

        if (empty($matching)) {
            // City unknown to dataset → can't reject what we don't know.
            return true;
        }

        foreach ($matching as $r) {
            if ($prefix >= $r['from'] && $prefix <= $r['to']) return true;
        }
        return false;
    }

    /** @return array<int, string> normalized canonical + alias names for a range. */
    private static function namesFor(array $r): array
    {
        $names = array_merge([$r['city']], $r['aliases'] ?? []);
        return array_map([self::class, 'normalize'], $names);
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
        // Common Dutch place-name noise: leading "'s-" or "s-" prefix (the
        // archaic genitive in 's-Gravenhage / 's-Hertogenbosch), trailing
        // parenthetical disambiguators ("(gld)" etc.), hyphens collapsed
        // to spaces, "den"/"de" prefix words stripped.
        $s = preg_replace("/^(?:'s-|s-)/u", '', $s);
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
