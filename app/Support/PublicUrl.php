<?php

namespace App\Support;

/**
 * Links naar de publieke site.
 *
 * De statische pagina's daar hebben per taal een eigen slug, vastgelegd in
 * slugs.json in de desnipperaar-repo. /voorwaarden wordt dus /en/terms en niet
 * /en/voorwaarden. Houd deze tabel gelijk aan die slugs.json, anders stuurt een
 * mail de klant naar een 301 of erger.
 */
class PublicUrl
{
    private const TERMS = [
        'nl' => 'voorwaarden',
        'en' => 'en/terms',
        'fr' => 'fr/conditions',
        'es' => 'es/condiciones',
    ];

    public static function base(): string
    {
        return rtrim((string) config('desnipperaar.public_url'), '/');
    }

    /**
     * Algemene voorwaarden in de taal van de klant. Onbekende taal valt terug
     * op Nederlands, net als de mailtemplates zelf.
     */
    public static function terms(?string $locale = 'nl'): string
    {
        return self::base().'/'.(self::TERMS[$locale] ?? self::TERMS['nl']);
    }
}
