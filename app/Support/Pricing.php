<?php

namespace App\Support;

class Pricing
{
    // Regular rates (excl. BTW)
    public const BOX_FIRST             = 30.00;
    public const BOX_NEXT               = 25.00;
    public const CONTAINER_FIRST       = 120.00;
    public const CONTAINER_NEXT        = 45.00;

    // Amsterdam-pilot rates (20% off)
    public const BOX_FIRST_PILOT       = 24.00;
    public const BOX_NEXT_PILOT        = 20.00;
    public const CONTAINER_FIRST_PILOT = 96.00;
    public const CONTAINER_NEXT_PILOT  = 36.00;

    public const VAT_RATE              = 0.21;

    // Pickup ("ophaal") surcharge for the "sooner" option outside the free zone.
    // Regio Amsterdam (first PICKUP_FREE_KM km, one-way road distance) is free.
    public const PICKUP_FREE_KM        = 20;
    public const PICKUP_RATE_PER_KM    = 0.65;

    /**
     * Authoritative pickup-cost calculation. The static site sends the km and the
     * chosen option, but the amount is always recomputed here so the client can
     * never dictate the price. Free ("gratis vanaf 2 weken") is always 0; "sooner"
     * costs EUR 0,65 per km beyond the first 20 km, one-way.
     */
    public static function pickupCost(?int $km, bool $sooner): float
    {
        if (!$sooner || $km === null || $km <= self::PICKUP_FREE_KM) {
            return 0.0;
        }
        return round(($km - self::PICKUP_FREE_KM) * self::PICKUP_RATE_PER_KM, 2);
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
        float $pickupCost = 0.0
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
        $subtotal        = round($quote['subtotal'] + $mediaSubtotal + $pickupCost, 2);
        $subtotalRegular = round($quote['subtotal_regular'] + $mediaSubtotalRegular + $pickupCost, 2);
        $discount        = round($subtotalRegular - $subtotal, 2);
        $vat             = round($subtotal * self::VAT_RATE, 2);
        $total           = round($subtotal + $vat, 2);

        return [
            'lines'            => $quote['lines'],
            'media_lines'      => $mediaLines,
            'pickup_cost'      => $pickupCost,
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
