<?php

return [

    'order' => [
        'prefix'        => env('DESNIPPERAAR_ORDER_PREFIX', 'B'),  // bestelling
        'quote_prefix'  => env('DESNIPPERAAR_QUOTE_PREFIX', 'O'),  // offerte
        'start'         => (int) env('DESNIPPERAAR_ORDER_START', 142),
    ],

    'bon' => [
        'prefix' => env('DESNIPPERAAR_BON_PREFIX', 'P'),
    ],

    'certificate' => [
        'prefix' => env('DESNIPPERAAR_CERT_PREFIX', 'C'),
    ],

    'pilot' => [
        'postcode_start' => 1020,
        'postcode_end'   => 1039,
        'discount_pct'   => 20,
    ],
];
