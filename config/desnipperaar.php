<?php

return [

    // Public customer-facing site. The pickup planning page lives there.
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

    'pickup' => [
        // Spoed ophalen. Drie standen, want spoed geldt nu wel in regio Amsterdam
        // en nog niet daarbuiten:
        //
        //   off     de keuze bestaat niet
        //   region  alleen binnen region_km, waar wij zelf rijden
        //   all      overal
        //
        // Buiten wat is toegestaan valt 'spoed' terug op 'sooner': de klant wilde
        // snel geholpen worden en dat kan, alleen rekenen wij geen toeslag die
        // wij daar nergens hebben aangeboden.
        //
        // Dit is het echte slot. De publieke site heeft zijn eigen schakelaar
        // (spoedPickup in site-config.json) die dezelfde standen kent, maar die
        // verbergt alleen; een vlag in de browser houdt niemand tegen die zelf
        // een formulier post. Zet ze samen om, anders ziet de klant iets anders
        // dan hij krijgt.
        'rush_mode' => env('PICKUP_RUSH_MODE', 'off'),
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

        // Wanneer wij per ISO-weekdag (1 = maandag) rijden, als kloktijden. Een
        // dag die hier niet staat wordt nooit aangeboden. Dit is de plek waar je
        // de planning krimpt of uitbreidt als de rijdagen veranderen.
        //
        // Uit deze reeksen komen de losse uurblokken die wij aanbieden, dus
        // 10:00-11:00 tot en met 15:00-16:00. Wij bieden een uur aan en geen
        // dagdeel, want dat is ook wat er uiteindelijk in pickup_window komt te
        // staan zodra er een tijd is afgesproken. Twee reeksen op een dag zetten
        // betekent een gat ertussen, bijvoorbeeld voor een vaste lunchpauze.
        //
        // Bewust smaller dan de werkdag. De randen van de dag gaan op aan laden,
        // lossen en de rit naar het eerste adres, dus die beloven wij niet weg.
        // Een rit die hier buiten valt kan altijd nog met de hand ingepland
        // worden; de velden op de orderpagina kennen deze grenzen niet.
        'week' => [
            1 => ['10:00-16:00'],
            2 => ['10:00-16:00'],
            3 => ['10:00-16:00'],
            4 => ['10:00-16:00'],
            5 => ['10:00-16:00'],
        ],

        // De lengte van een aangeboden blok, in minuten. Zestig geeft hele uren,
        // wat leest als een afspraak en niet als een algoritme.
        'slot_minutes' => (int) env('PLANNING_SLOT_MINUTES', 60),

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
        // nodig hebben.
        //
        // Op productie staat lead_days op 1 via de env, dus de eerstvolgende
        // werkdag mag. Dat is nodig zodra spoed aan gaat: staat lead_days gelijk
        // aan rush_days, dan verkoopt spoed precies de dag die iedereen toch al
        // gratis krijgt en koopt de klant lucht. Ruimer zetten betekent meer tijd
        // om te combineren, en een spoedoptie die meer voorstelt.
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

        // Het spoedvenster: een ophaling binnen zoveel dagen na vandaag geldt als
        // spoed. Hier staat geen bedrag meer bij. Wat spoed kost hoort thuis bij
        // het bestellen, waar de klant de keuze maakt en de prijs ziet voordat
        // hij afrekent, en niet in de planning die achteraf een toeslag zou
        // opplakken.
        'rush_days' => (int) env('PLANNING_RUSH_DAYS', 2),

        // Hoeveel blokken wij per dag vrijhouden zolang die dag nog buiten het
        // spoedvenster ligt. Zonder deze reservering loopt de agenda weken
        // vooruit vol met ritten die geen haast hadden, en kunnen wij een
        // spoedklant niets meer aanbieden. De reservering valt vanzelf vrij zodra
        // de dag binnen rush_days komt.
        'rush_reserve_slots' => (int) env('PLANNING_RUSH_RESERVE_SLOTS', 1),

        // Rechte lijn maal deze factor benadert de wegafstand. Zelfde factor als
        // de fallback in api/distance.js.
        'road_factor' => (float) env('PLANNING_ROAD_FACTOR', 1.3),

    ],

    /**
     * OBSOLEET sinds 2026-08-14. De Amsterdam-pilot is afgelopen en de korting
     * wordt niet meer gegeven.
     *
     * Blijft met opzet staan en wordt niet gesloopt: een handvol oude orders
     * draagt pilot=true, en hun bon, factuur en bevestigingsmail moeten die
     * kortingsregel blijven tonen zoals de klant hem destijds kreeg. Weghalen
     * betekent papier herschrijven dat de deur al uit is.
     *
     * Bouw er niets nieuws op. Wie hier langskomt voor een prijs- of
     * kortingsvraag: dit pad is dood, de enige levende korting per order is de
     * kortingscode.
     */
    'pilot' => [
        // Master switch. Staat uit en hoort uit te blijven. Zolang hij uit staat
        // krijgt geen enkele nieuwe order, group-deal-deelnemer of klant de
        // pilotvlag, en verdwijnen de 20% korting en de bijbehorende labels uit
        // intake, planning, offertes en mails. Bestaande rijen houden hun vlag.
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
