<?php

namespace App\Support;

class Pricing
{
    // Regular rates (excl. BTW)
    public const BOX_FIRST             = 30.00;
    public const BOX_NEXT               = 25.00;
    public const CONTAINER_FIRST       = 120.00;
    public const CONTAINER_NEXT        = 45.00;

    // Noord-pilot rates (20% off)
    public const BOX_FIRST_PILOT       = 24.00;
    public const BOX_NEXT_PILOT        = 20.00;
    public const CONTAINER_FIRST_PILOT = 96.00;
    public const CONTAINER_NEXT_PILOT  = 36.00;

    public const VAT_RATE              = 0.21;

    public const MEDIA_PRICES = [
        'hdd'    => 9,
        'ssd'    => 15,
        'usb'    => 6,
        'phone'  => 12,
        'laptop' => 19,
    ];

    public const MEDIA_LABELS = [
        'hdd'    => 'HDD',
        'ssd'    => 'SSD / NVMe',
        'usb'    => 'USB / SD',
        'phone'  => 'Telefoon / tablet',
        'laptop' => 'Laptop',
    ];

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
     * Postcode prefix range that gets the Noord-pilot 20% discount.
     */
    public static function isPilotPostcode(?string $postcode): bool
    {
        if (!$postcode) {
            return false;
        }
        $prefix = (int) substr(preg_replace('/\s+/', '', $postcode), 0, 4);
        return $prefix >= 1020 && $prefix <= 1039;
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

        $subtotal        = array_sum(array_column($lines, 'subtotal'));
        $subtotalRegular = array_sum(array_map(fn ($l) => $l['was_subtotal'] ?? $l['subtotal'], $lines));
        $discount        = round($subtotalRegular - $subtotal, 2);
        $vat             = round($subtotal * self::VAT_RATE, 2);
        $total           = round($subtotal + $vat, 2);

        return [
            'lines'            => $lines,
            'subtotal'         => round($subtotal, 2),
            'subtotal_regular' => round($subtotalRegular, 2),
            'discount'         => $discount,
            'vat'              => $vat,
            'total'            => $total,
            'pilot'            => $pilot,
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
        bool $firstBoxFree
    ): array {
        if ($pilot) {
            // Pilot replaces all organizer perks per the pricing rule.
            $firstBoxFree = false;
        }

        $quote = self::quote($boxes, $containers, $pilot, $firstBoxFree);

        $mediaLines = [];
        foreach (($mediaItems ?? []) as $key => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0 || !isset(self::MEDIA_PRICES[$key])) {
                continue;
            }
            $unit = self::MEDIA_PRICES[$key];
            $mediaLines[] = [
                'key'      => $key,
                'label'    => self::MEDIA_LABELS[$key] ?? ucfirst($key),
                'qty'      => $qty,
                'unit'     => $unit,
                'subtotal' => $unit * $qty,
            ];
        }

        $mediaSubtotal   = array_sum(array_column($mediaLines, 'subtotal'));
        $subtotal        = round($quote['subtotal'] + $mediaSubtotal, 2);
        $subtotalRegular = round($quote['subtotal_regular'] + $mediaSubtotal, 2);
        $discount        = round($subtotalRegular - $subtotal, 2);
        $vat             = round($subtotal * self::VAT_RATE, 2);
        $total           = round($subtotal + $vat, 2);

        return [
            'lines'            => $quote['lines'],
            'media_lines'      => $mediaLines,
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
