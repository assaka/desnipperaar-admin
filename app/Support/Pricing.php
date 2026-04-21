<?php

namespace App\Support;

class Pricing
{
    // Regular rates (excl. BTW)
    public const BOX_FIRST             = 30.00;
    public const BOX_NEXT              = 25.00;
    public const CONTAINER_FIRST       = 120.00;
    public const CONTAINER_NEXT        = 45.00;

    // Noord-pilot rates (20% off)
    public const BOX_FIRST_PILOT       = 24.00;
    public const BOX_NEXT_PILOT        = 20.00;
    public const CONTAINER_FIRST_PILOT = 96.00;
    public const CONTAINER_NEXT_PILOT  = 36.00;

    public const VAT_RATE              = 0.21;

    public static function quote(int $boxes, int $containers, bool $pilot = false, bool $firstBoxFree = false): array
    {
        $bFirst = $pilot ? self::BOX_FIRST_PILOT       : self::BOX_FIRST;
        $bNext  = $pilot ? self::BOX_NEXT_PILOT        : self::BOX_NEXT;
        $cFirst = $pilot ? self::CONTAINER_FIRST_PILOT : self::CONTAINER_FIRST;
        $cNext  = $pilot ? self::CONTAINER_NEXT_PILOT  : self::CONTAINER_NEXT;

        $lines = [];

        if ($boxes > 0) {
            if ($firstBoxFree) {
                $lines[] = ['label' => 'Kennismaking — eerste doos',         'qty' => 1,             'unit' => 0.00,   'subtotal' => 0.00];
                if ($boxes >= 2) {
                    $lines[] = ['label' => 'Daarna eerste doos',              'qty' => 1,             'unit' => $bFirst, 'subtotal' => $bFirst];
                }
                if ($boxes >= 3) {
                    $lines[] = ['label' => 'Volgende dozen',                  'qty' => $boxes - 2,    'unit' => $bNext,  'subtotal' => $bNext * ($boxes - 2)];
                }
            } else {
                $lines[] = ['label' => 'Eerste doos',                         'qty' => 1,             'unit' => $bFirst, 'subtotal' => $bFirst];
                if ($boxes >= 2) {
                    $lines[] = ['label' => 'Volgende dozen',                  'qty' => $boxes - 1,    'unit' => $bNext,  'subtotal' => $bNext * ($boxes - 1)];
                }
            }
        }

        if ($containers > 0) {
            $lines[] = ['label' => 'Eerste rolcontainer 240 L',               'qty' => 1,              'unit' => $cFirst, 'subtotal' => $cFirst];
            if ($containers >= 2) {
                $lines[] = ['label' => 'Volgende rolcontainers',              'qty' => $containers - 1, 'unit' => $cNext,  'subtotal' => $cNext * ($containers - 1)];
            }
        }

        $subtotal = array_sum(array_column($lines, 'subtotal'));
        $vat      = round($subtotal * self::VAT_RATE, 2);
        $total    = round($subtotal + $vat, 2);

        return [
            'lines'    => $lines,
            'subtotal' => round($subtotal, 2),
            'vat'      => $vat,
            'total'    => $total,
            'pilot'    => $pilot,
        ];
    }
}
