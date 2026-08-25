<?php

namespace App\Support;

class Pricing
{
    // Regular rates (excl. BTW)
    public const BOX_FIRST             = 30.00;
    public const BOX_NEXT               = 25.00;
    public const CONTAINER_FIRST       = 120.00;
    public const CONTAINER_NEXT        = 45.00;

    // Amsterdam-pilot rates (20% off).
    //
    // OBSOLEET sinds 2026-08-14, zie config/desnipperaar.php → pilot. Deze
    // tarieven worden alleen nog geraakt door oude orders die de vlag dragen,
    // zodat hun factuur en bon tonen wat de klant destijds betaalde. Geen nieuwe
    // order komt hier langs.
    public const BOX_FIRST_PILOT       = 24.00;
    public const BOX_NEXT_PILOT        = 20.00;
    public const CONTAINER_FIRST_PILOT = 96.00;
    public const CONTAINER_NEXT_PILOT  = 36.00;

    public const VAT_RATE              = 0.21;

    // Pickup ("ophaal") surcharge for the "sooner" option outside the free zone.
    // De eerste kilometers zijn gratis, ongeacht hoe snel de klant wil.
    //
    // Deze twee zijn de terugval en niet de waarheid: de stand van dienst staat
    // in config/desnipperaar.php -> pickup, met dezelfde omgevingsvariabelen als
    // de publieke site. Lees ze via freeKm() en ratePerKm() en niet rechtstreeks,
    // anders rekent deze kant een andere prijs dan de klant op /order zag.
    public const PICKUP_FREE_KM        = 35;
    public const PICKUP_RATE_PER_KM    = 0.65;

    /** Gratis ophaalstraal in km, enkele reis over de weg. */
    public static function freeKm(): float
    {
        return (float) config('desnipperaar.pickup.free_km', self::PICKUP_FREE_KM);
    }

    /** Kilometerprijs boven die straal, euro per km enkele reis. */
    public static function ratePerKm(): float
    {
        return (float) config('desnipperaar.pickup.rate_per_km', self::PICKUP_RATE_PER_KM);
    }

    /**
     * Het bestelbedrag ex btw waarboven de kilometerprijs voor "eerder ophalen"
     * vervalt, voor deze afstand.
     *
     * Een rechte lijn en geen treden, want treden geven klifjes: op 60 km gratis
     * en op 61 km ineens het volle bedrag is niet uit te leggen. Tot base_km het
     * basisbedrag, daarboven per_km erbij per extra kilometer. Voorbij max_km
     * valt er niets te halen en geeft dit 0.0.
     *
     * Dezelfde lijn staat op de publieke site in site-config.json -> pickup.
     * Loopt hij uiteen, dan wijkt de factuur af van wat de klant zag.
     */
    public static function freeAboveSubtotal(?int $km): float
    {
        if ($km === null) {
            return 0.0;
        }
        $base   = (float) config('desnipperaar.pickup.free_above.base', 100);
        $baseKm = (float) config('desnipperaar.pickup.free_above.base_km', 50);
        $perKm  = (float) config('desnipperaar.pickup.free_above.per_km', 5);
        $maxKm  = (float) config('desnipperaar.pickup.free_above.max_km', 70);

        if ($km > $maxKm) {
            return 0.0;
        }

        return round($base + max(0.0, $km - $baseKm) * $perKm, 2);
    }

    /**
     * Valt de kilometerprijs weg voor deze order?
     *
     * Twee voorwaarden tegelijk: de klant woont binnen de afstand waar de
     * vrijstelling nog geldt, en het mandje haalt het bedrag dat daar hoort. Het subtotaal is dat van de goederen ex btw en na coupon,
     * dus zonder de ophaalkosten zelf, anders zou de vrijstelling zichzelf mede
     * verdienen.
     */
    public static function pickupFeeWaived(?int $km, float $goodsSubtotal): bool
    {
        $need = self::freeAboveSubtotal($km);

        return $need > 0 && $goodsSubtotal >= $need;
    }

    /**
     * Spoedtoeslag: vast bedrag voor ophalen binnen een paar werkdagen.
     *
     * Bovenop de kilometerprijs en niet in plaats daarvan, zoals expreslevering
     * bij een pakket: het rijden kost wat het kost, en spoed is de extra dienst
     * daar bovenop. Een klant in Gorinchem betaalt dus 38,81 + 15,00 en niet
     * alleen de 15,00.
     */
    public const PICKUP_RUSH_FEE       = 15.00;

    /**
     * Authoritative pickup-cost calculation. The static site sends the km and the
     * chosen option, but the amount is always recomputed here so the client can
     * never dictate the price. Free ("gratis vanaf 2 weken") is always 0; both
     * "sooner" and "spoed" cost the per-km rate beyond the free radius, one-way.
     *
     * Boven het drempelbedrag rijden wij dat eerdere ophalen zelf, tot de afstand
     * waarop dat nog uitkan. Daarom heeft deze som het goederensubtotaal nodig.
     * Wie hem zonder aanroept krijgt de oude uitkomst, dus zonder vrijstelling:
     * te veel rekenen valt op, te weinig rekenen niet.
     */
    public static function pickupCost(?int $km, bool $sooner, float $goodsSubtotal = 0.0): float
    {
        $freeKm = self::freeKm();

        if (!$sooner || $km === null || $km <= $freeKm) {
            return 0.0;
        }
        if (self::pickupFeeWaived($km, $goodsSubtotal)) {
            return 0.0;
        }

        return round(($km - $freeKm) * self::ratePerKm(), 2);
    }

    /**
     * De spoedtoeslag, los van de afstand.
     *
     * Er zit met opzet geen kilometergrens op. Spoed is tijd en geen afstand, dus
     * ook een klant in regio Amsterdam kan hem kopen; die betaalt dan alleen de
     * toeslag, want zijn rit is gratis.
     */
    public static function pickupRushFee(bool $rush): float
    {
        return $rush ? self::PICKUP_RUSH_FEE : 0.0;
    }

    // Base (tier-0) per-unit prices, excl. BTW. Match the public site (order.html)
    // and prijzen.html. These equal MEDIA_TIERS[key][0].
    public const MEDIA_PRICES = [
        'hdd'     => 8,
        'ssd'     => 8,
        'usb'     => 1.5,
        'phone'   => 9,
        'laptop'  => 12.5,
        'printer' => 20,
        'tape'    => 1.5,
    ];

    public const MEDIA_LABELS = [
        'hdd'     => 'HDD',
        'ssd'     => 'SSD / NVMe',
        'usb'     => 'USB / SD',
        'phone'   => 'Telefoon / tablet',
        'laptop'  => 'Laptop',
        'printer' => 'Printer / kopieerapparaat',
        'tape'    => 'Backup-tape (LTO)',
    ];

    // Volume staffel per carrier type: unit price at tiers
    // 1–24 / 25–99 / 100–499 / 500+. Mirrors the public site (order.html
    // mediaItems[].tiers) and docs/order-backend-pricing-spec.md. Laptop and
    // printer have no published staffel, so all tiers are equal (flat).
    public const MEDIA_TIERS = [
        'hdd'     => [8, 6.5, 5, 4],
        'ssd'     => [8, 6.5, 5, 4],
        'usb'     => [1.5, 1.25, 1, 0.75],
        'tape'    => [1.5, 1.25, 1, 0.75],
        'phone'   => [9, 7.5, 6, 4.75],
        'laptop'  => [12.5, 12.5, 12.5, 12.5],
        'printer' => [20, 20, 20, 20],
    ];

    /** Tier index for a data-carrier quantity (per carrier type). */
    public static function mediaTierIndex(int $qty): int
    {
        if ($qty >= 500) return 3;
        if ($qty >= 100) return 2;
        if ($qty >= 25)  return 1;
        return 0;
    }

    /** Staffel unit price for a carrier type at the given quantity. 0 for unknown keys. */
    public static function mediaUnit(string $key, int $qty): float
    {
        if (!isset(self::MEDIA_TIERS[$key])) return 0.0;
        return (float) self::MEDIA_TIERS[$key][self::mediaTierIndex($qty)];
    }

    // Richer labels used on the invoice, which relabels the lines it copies from
    // mediaLine(). Kept here so line-type detection has a single home.
    public const MEDIA_LABELS_INVOICE = [
        'hdd'     => 'HDD / harde schijf',
        'ssd'     => 'SSD / NVMe',
        'usb'     => 'USB-stick / SD',
        'phone'   => 'Telefoon / tablet',
        'laptop'  => 'Laptop',
        'printer' => 'Printer / kopieerapparaat',
        'tape'    => 'Backup-tape (LTO)',
    ];

    /**
     * True when a priced line is a data-carrier line, i.e. its only possible
     * discount is the volume staffel. Kennismaking and the Amsterdam pilot both
     * discount box/container lines instead, so they must not be confused with it.
     * Lines built from now on carry `kind`; invoice snapshots stored before that
     * are matched on their label, in either the plain or the invoice spelling.
     */
    public static function isMediaLine(array $line): bool
    {
        if (($line['kind'] ?? null) === 'media') {
            return true;
        }
        $label = $line['label'] ?? '';

        return in_array($label, self::MEDIA_LABELS, true)
            || in_array($label, self::MEDIA_LABELS_INVOICE, true);
    }

    /** True when a priced line is the kortingscode line built by couponLine(). */
    public static function isCouponLine(array $line): bool
    {
        return ($line['kind'] ?? null) === 'coupon';
    }

    /** De regels waarmee de rit naar de klant wordt doorbelast. */
    public const PICKUP_LABELS = [
        'Eerder ophalen (binnen 2 weken)',
        'Spoedtoeslag ophalen',
    ];

    /**
     * True voor een regel die de ophaalrit doorbelast.
     *
     * Die regels staan buiten de grondslag van een kortingscode. Het
     * winkelwagentje op /order trekt de korting van het artikelsubtotaal af en
     * zet de ophaalkosten er daarna bovenop, dus een actiecode geeft geen korting
     * op gereden kilometers. Zelfde vorm als isMediaLine(): regels dragen `kind`,
     * en oudere factuursnapshots worden op hun label herkend.
     */
    public static function isPickupLine(array $line): bool
    {
        if (($line['kind'] ?? null) === 'pickup') {
            return true;
        }

        return in_array($line['label'] ?? '', self::PICKUP_LABELS, true);
    }

    /**
     * De kortingscode als eigen regel met een negatief bedrag.
     *
     * Bewust een regel en geen aparte totaalrij: de factuurtemplates leiden de
     * kortingen af uit het verschil tussen de regels en amount_excl_btw, en
     * schrijven wat er dan nog overblijft toe aan de Amsterdam-pilot. Een korting
     * die alleen in de totalen zou zitten, komt daardoor in alle vier de talen op
     * de factuur te staan als "Korting Amsterdam-pilot". Als regel blijft
     * sum(subtotal) == amount_excl_btw en klopt elke template zonder aanpassing.
     */
    public static function couponLine(
        string $code,
        float $discount,
        ?string $type = null,
        ?float $value = null,
        ?float $base = null
    ): array {
        $amount = -round(abs($discount), 2);

        $code = strtoupper(trim($code));

        $row = [
            // De code staat ook los in `code`, zodat de anderstalige facturen hun
            // eigen aanhef kunnen zetten zonder het label te moeten uitsplitsen.
            'label'    => 'Kortingscode '.$code,
            'kind'     => 'coupon',
            'code'     => $code,
            'qty'      => 1,
            'unit'     => $amount,
            'subtotal' => $amount,
        ];

        // Percentage en grondslag gaan alleen mee als de som ook echt uitkomt.
        // Is de korting afgetopt op het factuurbedrag, dan zou "25% × € 55,00"
        // een bedrag beloven dat er niet staat, en dan is de code zonder
        // rekensom eerlijker dan een kloppend uitziende regel die niet klopt.
        if ($type === 'percentage' && $value > 0 && $base > 0
            && round($base * $value / 100, 2) === round(abs($discount), 2)) {
            $row['pct']  = round($value, 2);
            $row['base'] = round($base, 2);
        }

        return $row;
    }

    /** 25.00 wordt "25", 12.50 wordt "12,5": een percentage leest niet als bedrag. */
    public static function formatPercentage(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ''), '0'), ',');
    }

    /**
     * One priced media line with the volume staffel applied. Sets
     * was_unit/was_subtotal when the tiered unit is below the base (tier-0) price,
     * so templates render the discount the same way as box/container discounts.
     * Returns null for unknown keys or non-positive quantities.
     */
    public static function mediaLine(string $key, int $qty): ?array
    {
        $qty = (int) $qty;
        if ($qty <= 0 || !isset(self::MEDIA_TIERS[$key])) {
            return null;
        }
        $base = (float) self::MEDIA_TIERS[$key][0];
        $unit = self::mediaUnit($key, $qty);
        $row = [
            'label'    => self::MEDIA_LABELS[$key] ?? ucfirst($key),
            'kind'     => 'media',
            'qty'      => $qty,
            'unit'     => $unit,
            'subtotal' => round($unit * $qty, 2),
        ];
        if ($base > $unit) {
            $row['was_unit']     = $base;
            $row['was_subtotal'] = round($base * $qty, 2);
        }
        return $row;
    }

    /**
     * Organizer commission, in euros: pct% of joiners' total subtotal.
     * Caller passes in the joiners' summed subtotal_after_perks; this method
     * just multiplies by the configured percentage. Pilot organizers should
     * receive 0 (caller's responsibility — pilot replaces all perks).
     */
    public static function organizerCommissionAmount(float $joinersSubtotal): float
    {
        $pct = (int) config('desnipperaar.group_deal.organizer_commission_pct', 0);
        if ($pct <= 0 || $joinersSubtotal <= 0) return 0.0;
        return round($joinersSubtotal * $pct / 100, 2);
    }

    /**
     * Postcode prefix range that gets the Amsterdam-pilot 20% discount.
     * Range comes from config (desnipperaar.pilot) so it stays in sync with OrderController.
     *
     * @deprecated 2026-08-14 De pilot is afgelopen. Geeft altijd false zolang de
     *             schakelaar uit staat, zie config/desnipperaar.php → pilot.
     */
    public static function isPilotPostcode(?string $postcode): bool
    {
        if (!$postcode || !config('desnipperaar.pilot.enabled')) {
            return false;
        }
        $prefix = (int) substr(preg_replace('/\s+/', '', $postcode), 0, 4);
        return $prefix >= (int) config('desnipperaar.pilot.postcode_start')
            && $prefix <= (int) config('desnipperaar.pilot.postcode_end');
    }

    /**
     * Build one priced line, attaching was_unit / was_subtotal when the effective
     * unit is below the regular rate — consumers can then sum was_subtotal ?? subtotal
     * to reconstruct the pre-discount total without duplicating pricing logic.
     */
    private static function line(string $label, int $qty, float $unit, float $regularUnit): array
    {
        $row = [
            'label'    => $label,
            'qty'      => $qty,
            'unit'     => $unit,
            'subtotal' => $unit * $qty,
        ];
        if ($regularUnit > $unit) {
            $row['was_unit']     = $regularUnit;
            $row['was_subtotal'] = $regularUnit * $qty;
        }
        return $row;
    }

    /**
     * @param bool $pilot OBSOLEET, zie config/desnipperaar.php → pilot. Alleen
     *                    nog true bij het opnieuw opbouwen van een oude order die
     *                    de vlag draagt. Zet hem nergens met de hand aan.
     */
    public static function quote(int $boxes, int $containers, bool $pilot = false, bool $firstBoxFree = false): array
    {
        $bFirst = $pilot ? self::BOX_FIRST_PILOT       : self::BOX_FIRST;
        $bNext  = $pilot ? self::BOX_NEXT_PILOT        : self::BOX_NEXT;
        $cFirst = $pilot ? self::CONTAINER_FIRST_PILOT : self::CONTAINER_FIRST;
        $cNext  = $pilot ? self::CONTAINER_NEXT_PILOT  : self::CONTAINER_NEXT;

        $lines = [];

        if ($boxes > 0) {
            if ($firstBoxFree) {
                $lines[] = self::line('Kennismaking — eerste doos', 1, 0.00, self::BOX_FIRST);
                if ($boxes >= 2) {
                    $lines[] = self::line('Daarna eerste doos', 1, $bFirst, self::BOX_FIRST);
                }
                if ($boxes >= 3) {
                    $lines[] = self::line('Volgende dozen', $boxes - 2, $bNext, self::BOX_NEXT);
                }
            } else {
                $lines[] = self::line('Eerste doos', 1, $bFirst, self::BOX_FIRST);
                if ($boxes >= 2) {
                    $lines[] = self::line('Volgende dozen', $boxes - 1, $bNext, self::BOX_NEXT);
                }
            }
        }

        if ($containers > 0) {
            $lines[] = self::line('Eerste rolcontainer 240 L', 1, $cFirst, self::CONTAINER_FIRST);
            if ($containers >= 2) {
                $lines[] = self::line('Volgende rolcontainers', $containers - 1, $cNext, self::CONTAINER_NEXT);
            }
        }

        $subtotal              = array_sum(array_column($lines, 'subtotal'));
        $subtotalRegular       = array_sum(array_map(fn ($l) => $l['was_subtotal'] ?? $l['subtotal'], $lines));
        $discount              = round($subtotalRegular - $subtotal, 2);
        $discountKennismaking  = round(array_sum(array_map(fn ($l) => ($l['unit'] == 0 && isset($l['was_subtotal'])) ? $l['was_subtotal'] : 0, $lines)), 2);
        $discountPilot         = round($discount - $discountKennismaking, 2);
        $vat                   = round($subtotal * self::VAT_RATE, 2);
        $total                 = round($subtotal + $vat, 2);

        return [
            'lines'                 => $lines,
            'subtotal'              => round($subtotal, 2),
            'subtotal_regular'      => round($subtotalRegular, 2),
            'discount'              => $discount,
            'discount_kennismaking' => $discountKennismaking,
            'discount_pilot'        => $discountPilot,
            'vat'                   => $vat,
            'total'                 => $total,
            'pilot'                 => $pilot,
        ];
    }

    /**
     * Build a full priced snapshot including media line items, ready to be persisted
     * on a group-deal participant or on a quote_locked order. Mirrors the shape that
     * OrderCreated's content() method reconstructs for non-locked orders, so the
     * email/invoice templates can render either source without branching.
     *
     * Pilot/perk rules:
     *  - $pilot is the authoritative pilot flag (caller decides; usually
     *    Pricing::isPilotPostcode($postcode)).
     *  - When pilot is true, the organizer perk is suppressed (pilot replaces perk),
     *    so $firstBoxFree is forced to false in that case.
     */
    public static function snapshot(
        int $boxes,
        int $containers,
        ?array $mediaItems,
        bool $pilot,
        bool $firstBoxFree,
        float $pickupCost = 0.0,
        float $pickupRushFee = 0.0
    ): array {
        if ($pilot) {
            // Pilot replaces all organizer perks per the pricing rule.
            $firstBoxFree = false;
        }

        $quote = self::quote($boxes, $containers, $pilot, $firstBoxFree);

        $mediaLines = [];
        foreach (($mediaItems ?? []) as $key => $qty) {
            $line = self::mediaLine($key, (int) $qty);
            if ($line === null) {
                continue;
            }
            $mediaLines[] = ['key' => $key] + $line;
        }

        $mediaSubtotal        = array_sum(array_column($mediaLines, 'subtotal'));
        // Media staffel carries a discount, so the "regular" side uses the base
        // (was_subtotal) where present. Pickup cost carries no discount.
        $mediaSubtotalRegular = array_sum(array_map(fn ($l) => $l['was_subtotal'] ?? $l['subtotal'], $mediaLines));
        $pickupCost      = round(max(0, $pickupCost), 2);
        $pickupRushFee   = round(max(0, $pickupRushFee), 2);
        $subtotal        = round($quote['subtotal'] + $mediaSubtotal + $pickupCost + $pickupRushFee, 2);
        $subtotalRegular = round($quote['subtotal_regular'] + $mediaSubtotalRegular + $pickupCost + $pickupRushFee, 2);
        $discount        = round($subtotalRegular - $subtotal, 2);
        $vat             = round($subtotal * self::VAT_RATE, 2);
        $total           = round($subtotal + $vat, 2);

        return [
            'lines'            => $quote['lines'],
            'media_lines'      => $mediaLines,
            'pickup_cost'      => $pickupCost,
            'pickup_rush_fee'  => $pickupRushFee,
            'subtotal'         => $subtotal,
            'subtotal_regular' => $subtotalRegular,
            'discount'         => $discount,
            'vat'              => $vat,
            'total'            => $total,
            'pilot'            => $pilot,
            'first_box_free'   => $firstBoxFree,
            'pricing_version'  => 1,
            'computed_at'      => now()->toIso8601String(),
        ];
    }
}
