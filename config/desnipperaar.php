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

    'invoice' => [
        'prefix'         => env('DESNIPPERAAR_INVOICE_PREFIX', 'F'),
        'start'          => (int) env('DESNIPPERAAR_INVOICE_START', 1),
        'payment_terms_days' => (int) env('DESNIPPERAAR_PAYMENT_TERMS_DAYS', 14),
    ],

    'notifications' => [
        // sales@ is the From-address on customer-facing mails (OrderCreated, QuoteRequested);
        // admin_email gets a silent BCC so the team inbox receives a copy.
        'sales_email' => env('SALES_EMAIL',         'sales@desnipperaar.nl'),
        'admin_email' => env('ADMIN_NOTIFY_EMAIL',  'sales@desnipperaar.nl'),
    ],

    'group_deal' => [
        // Perk applied to the organizer's order at materialization. Currently only
        // 'first_box_free' is wired up; extend the Pricing snapshot helper if more
        // perk types get added.
        'organizer_perk_type' => env('GROUP_DEAL_ORGANIZER_PERK', 'first_box_free'),

        // Organizer commission: a percentage of joiners' bills credited back
        // against the organizer's own bill. Recruits → revenue from joiners
        // (full price, no margin cut on their items) → organizer earns a
        // proportional kickback that reduces what THEY owe. Capped at the
        // organizer's own pre-credit bill (commission can never make their
        // total go negative). Suppressed for pilot organizers — pilot wins.
        'organizer_commission_pct' => (int) env('GROUP_DEAL_ORGANIZER_COMMISSION_PCT', 10),

        // Master switch for the organizer-bonus payout flow. When false, the
        // close-deal command skips the bonus email + IBAN-request even if
        // commission_pct > 0. Lets us ship the feature behind a flag and turn
        // it on per environment.
        'organizer_bonus_enabled'  => filter_var(env('GROUP_DEAL_ORGANIZER_BONUS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

        // Hard cap on participants per deal.
        'max_joiners'         => (int) env('GROUP_DEAL_MAX_JOINERS', 30),

        // Minimum group target the organizer can propose. Use this to keep the
        // smallest deal economically viable (e.g. min 20 boxes to make a route
        // worth driving). Set to 1 (default) to allow any positive target.
        'min_target_boxes'        => (int) env('GROUP_DEAL_MIN_TARGET_BOXES', 1),
        'min_target_containers'   => (int) env('GROUP_DEAL_MIN_TARGET_CONTAINERS', 0),

        // Joining closes T-N days before pickup_date; the cron materializes orders
        // at that boundary.
        'join_cutoff_days'    => (int) env('GROUP_DEAL_JOIN_CUTOFF_DAYS', 2),

        // Pickup-date validation window at deal-creation time.
        'min_horizon_days'    => (int) env('GROUP_DEAL_MIN_HORIZON_DAYS', 7),
        'max_horizon_days'    => (int) env('GROUP_DEAL_MAX_HORIZON_DAYS', 90),

        // Hard rule: cannot have two non-rejected/cancelled deals on the same
        // (city, pickup_date). Kept enabled by default.
        'one_per_city_per_day' => (bool) env('GROUP_DEAL_ONE_PER_CITY_PER_DAY', true),
    ],

    'company' => [
        'name'     => env('COMPANY_NAME', 'DeSnipperaar'),
        'address'  => env('COMPANY_ADDRESS', ''),
        'postcode' => env('COMPANY_POSTCODE', ''),
        'city'     => env('COMPANY_CITY', 'Amsterdam'),
        'country'  => env('COMPANY_COUNTRY', 'Nederland'),
        'kvk'      => env('COMPANY_KVK', ''),
        'btw'      => env('COMPANY_BTW', ''),
        'iban'     => env('COMPANY_IBAN', ''),
        'bic'      => env('COMPANY_BIC', ''),
        'phone'    => env('COMPANY_PHONE', '06-10229965'),
        'email'    => env('COMPANY_EMAIL', 'sales@desnipperaar.nl'),
        'website'  => env('COMPANY_WEBSITE', 'desnipperaar.nl'),
    ],
];
