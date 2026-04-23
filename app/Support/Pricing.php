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
}
