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

        // Reistijd die een stop bovenop zijn eigen duur kost, naar rato van de
        // omweg. travel_minutes_nearby is de ondergrens (uitstappen en weer
        // wegrijden kost ook tijd), travel_minutes de bovengrens: het dagdeel
        // zegt wanneer wij aankomen, niet hoe lang wij onderweg waren.
        'travel_minutes'        => (int) env('PLANNING_TRAVEL_MINUTES', 25),
        'travel_minutes_nearby' => (int) env('PLANNING_TRAVEL_MINUTES_NEARBY', 10),
        'minutes_per_km'        => (float) env('PLANNING_MINUTES_PER_KM', 1.5),

        // Hoe ver vooruit wij momenten aanbieden, en hoeveel dagen wij minimaal
        // nodig hebben. Morgen aanbieden kan niet: de dag ervoor gaat de
        // herinnering uit en de bus wordt geladen.
        'horizon_days' => (int) env('PLANNING_HORIZON_DAYS', 28),
        'lead_days'    => (int) env('PLANNING_LEAD_DAYS', 2),

        // Tot zoveel extra kilometers omweg beschouwen wij een klant als "op de
        // route": hij kost dan weinig extra rijtijd en krijgt op het planbord de
        // groene markering. Het maakt de rit niet goedkoper voor de klant, want
        // de ophaalprijs staat al vast bij het bestellen; het maakt hem goedkoper
        // voor ons. 25 km is ongeveer een half uur extra rijden.
        'on_route_detour_km' => (float) env('PLANNING_ON_ROUTE_DETOUR_KM', 25),

        // De grens van regio Amsterdam, gelijk aan de 20 km op /werkgebied en in
        // api/distance.js. Binnen deze straal is ophalen gratis.
        //
        // Buiten de regio worden deze eerste 20 km afgetrokken, precies zoals
        // order.html rekent: max(0, km - 20) * per_km. Wij houden die formule
        // hier gelijk zodat het bedrag op het planbord en het bedrag op de
        // bevestiging dezelfde herkomst hebben. Bijkomend voordeel: er is geen
        // sprong op de grens. Wie op 20,1 km woont betaalt zeven cent en niet
        // dertien euro.
        'region_km' => (float) env('PLANNING_REGION_KM', 20),

        // Tarief per kilometer voor een ophaling buiten de regio. Zelfde bedrag
        // als op /order.
        'per_km' => (float) env('PLANNING_PER_KM', 0.65),

        // De wachttijd die hoort bij de gratis optie buiten regio Amsterdam.
        // Op /order staat "gratis ophalen met minimaal 2 weken wachttijd", en
        // dit is die twee weken. Het is geen willekeurige drempel: die tijd
        // hebben wij nodig om de rit te laten samenvallen met een andere rit in
        // de buurt. Wie eerder wil betaalt per kilometer, dat is de keuze
        // "sooner" op /order. Binnen de regio geldt de wachttijd niet, daar is
        // ophalen gratis zonder voorwaarde.
        'free_wait_days' => (int) env('PLANNING_FREE_WAIT_DAYS', 14),

        // Spoed. Een ophaling binnen zoveel dagen na vandaag kost een vast
        // bedrag extra, bovenop de ophaalprijs die uit het adres volgt.
        //
        // Dit is geen prijs die meebeweegt met hoe vol de agenda staat. Het
        // bedrag is vast en vooraf bekend, en het staat tegenover iets dat de
        // klant ook echt krijgt: voorrang. Wie op woensdag bestelt en vrijdag
        // opgehaald wil worden, schuift voor een geplande rit langs. Zet
        // rush_fee op 0 om de spoedoptie uit te zetten.
        'rush_days' => (int) env('PLANNING_RUSH_DAYS', 2),
        'rush_fee'  => (float) env('PLANNING_RUSH_FEE', 15.00),

        // Hoeveel minuten wij per dag vrijhouden zolang die dag nog buiten het
        // spoedvenster ligt. Zonder deze reservering loopt de agenda weken
        // vooruit vol met gewone ritten en kunnen wij een spoedklant niets meer
        // aanbieden, precies op het moment dat hij bereid is ervoor te betalen.
        // De reservering valt vanzelf vrij zodra de dag binnen rush_days komt.
        'rush_reserve_minutes' => (int) env('PLANNING_RUSH_RESERVE_MINUTES', 60),

        // Rechte lijn maal deze factor benadert de wegafstand. Zelfde factor als
        // de fallback in api/distance.js.
        'road_factor' => (float) env('PLANNING_ROAD_FACTOR', 1.3),

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
