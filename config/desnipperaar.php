<?php

return [

    // Public customer-facing site. The reschedule (herplan) page lives there.
    'public_url' => env('PUBLIC_SITE_URL', 'https://desnipperaar.nl'),

    'order' => [
        'prefix'        => env('DESNIPPERAAR_ORDER_PREFIX', 'B'),  // bestelling
        'quote_prefix'  => env('DESNIPPERAAR_QUOTE_PREFIX', 'O'),  // offerte
        'sub_prefix'    => env('DESNIPPERAAR_SUB_PREFIX',   'A'),  // abonnement
        'start'         => (int) env('DESNIPPERAAR_ORDER_START', 142),
    ],

    'bon' => [
        'prefix' => env('DESNIPPERAAR_BON_PREFIX', 'P'),
    ],

    'subscription' => [
        // Prijs per 240 L rolcontainer, excl. btw. Flex en Vast zijn per maand,
        // Jaar is een vooruitbetaling voor twaalf maanden. Deze tabel moet gelijk
        // blijven aan order.html op desnipperaar.nl en aan
        // docs/subscription-lead-spec.md in die repo. De aanvraag stuurt alleen
        // looptijd en frequentie mee, nooit een prijs: die rekenen we hier.
        'prices' => [
            'flex' => ['4w' => 34.95, '2w' => 54.95, '1w' => 94.95, '2pw' => 164.95],
            'vast' => ['4w' => 29.95, '2w' => 49.95, '1w' => 89.95, '2pw' => 159.95],
            'jaar' => ['4w' => 329.00, '2w' => 549.00, '1w' => 989.00, '2pw' => 1759.00],
        ],

        // Logistieke retourkosten bij opzeggen van Flex binnen twaalf maanden.
        // Gepubliceerd op /rolcontainer-huren als de werkelijke kosten van de
        // retourrit, uitdrukkelijk geen boete. Wijzig hier en op die pagina samen.
        'return_cost' => (float) env('DESNIPPERAAR_SUB_RETURN_COST', 75.00),
    ],

    'certificate' => [
        'prefix' => env('DESNIPPERAAR_CERT_PREFIX', 'C'),
    ],

    /**
     * Ophaalplanning. Bepaalt welke momenten wij kunnen aanbieden en wanneer een
     * rit "gratis" is omdat wij die dag toch al in de buurt zijn.
     *
     * Dit is geen routeoptimalisatie. Wij rijden een handvol stops per dag; wat
     * telt is of er nog ruimte in een dagdeel is en of de nieuwe stop bij de
     * stops van die dag in de buurt ligt. Beide vragen zijn met een afstand en
     * een optelsom te beantwoorden, en dat is precies wat SlotFinder doet.
     */
    'planning' => [
        // Vertrekpunt, 1034DN Amsterdam. Zelfde punt als api/distance.js op de
        // publieke site gebruikt; wijk je hier af dan rekenen site en admin
        // verschillende kilometers voor dezelfde klant.
        'depot' => [
            'lat' => (float) env('PLANNING_DEPOT_LAT', 52.388),
            'lon' => (float) env('PLANNING_DEPOT_LON', 4.945),
        ],

        // Welke dagdelen wij per ISO-weekdag (1 = maandag) rijden. Een dag die
        // hier niet staat wordt nooit aangeboden. Dit is de plek waar je de
        // planning krimpt of uitbreidt als de rijdagen veranderen.
        'week' => [
            1 => ['ochtend', 'middag'],
            2 => ['ochtend', 'middag'],
            3 => ['ochtend', 'middag'],
            4 => ['ochtend', 'middag'],
            5 => ['ochtend', 'middag'],
        ],

        // Netto rijtijd per dagdeel, in minuten. Bewust lager dan de klok: de
        // vensters lopen 08:00-12:00 en 12:00-17:00, maar niemand rijdt vier
        // uur aaneen zonder laden, lossen of koffie.
        'capacity_minutes' => [
            'ochtend' => (int) env('PLANNING_CAPACITY_OCHTEND', 180),
            'middag'  => (int) env('PLANNING_CAPACITY_MIDDAG', 210),
            'avond'   => (int) env('PLANNING_CAPACITY_AVOND', 120),
        ],

        // Duur van een stop als de order er zelf geen heeft staan.
        'default_duration' => (int) env('PLANNING_DEFAULT_DURATION', 30),

        // Reistijd die een stop bovenop zijn eigen duur kost. Ligt de stop bij
        // een al geplande stop in de buurt (binnen cluster_km) dan rijden wij er
        // toch al langs en kost hij alleen de korte variant.
        'travel_minutes'        => (int) env('PLANNING_TRAVEL_MINUTES', 25),
        'travel_minutes_nearby' => (int) env('PLANNING_TRAVEL_MINUTES_NEARBY', 10),

        // Hoe ver vooruit wij momenten aanbieden, en hoeveel dagen wij minimaal
        // nodig hebben. Morgen aanbieden kan niet: de dag ervoor gaat de
        // herinnering uit en de bus wordt geladen.
        'horizon_days' => (int) env('PLANNING_HORIZON_DAYS', 28),
        'lead_days'    => (int) env('PLANNING_LEAD_DAYS', 2),

        // Binnen deze straal van een al geplande stop rijden wij "toch al
        // langs". Dat maakt het moment gratis, ook buiten regio Amsterdam.
        'cluster_km' => (float) env('PLANNING_CLUSTER_KM', 12),

        // Regio Amsterdam: altijd gratis ophalen, ongeacht de dag. Gelijk aan
        // de 20 km op /werkgebied en in api/distance.js.
        'free_radius_km' => (float) env('PLANNING_FREE_RADIUS_KM', 20),

        // Tarief voor een rit die wij speciaal voor deze klant maken, per km
        // boven de eerste free_radius_km. Zelfde bedrag als op /order.
        'per_km' => (float) env('PLANNING_PER_KM', 0.65),

        // Rechte lijn maal deze factor benadert de wegafstand. Zelfde factor als
        // de fallback in api/distance.js.
        'road_factor' => (float) env('PLANNING_ROAD_FACTOR', 1.3),

        // Hoeveel momenten de klant maximaal te zien krijgt. Een lijst van
        // veertig velden kiest niemand uit.
        'max_offered' => (int) env('PLANNING_MAX_OFFERED', 12),
    ],

    'pilot' => [
        // Master switch for the Amsterdam pilot. When false, no new order,
        // group-deal participant, or customer is ever flagged as pilot, so the
        // 20% pilot discount and its badges/labels disappear from order intake,
        // planning, quotes and emails. Historical orders keep their stored
        // pilot flag, so past bons/invoices still render their pilot line.
        'enabled'        => env('PILOT_ENABLED', false),
        'postcode_start' => 1011,
        'postcode_end'   => 1109,
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
