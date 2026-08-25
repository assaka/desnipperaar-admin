{{--
    De ophaalsnelheid die de klant bij het bestellen zelf koos, als label.

    Kleur naar de haast die erachter zit: rood voor spoed, want dat is de enige
    die een dag kost als je hem laat liggen. Zwart-geel voor eerder, dat is een
    betaalde rit en die moet ook rijden. Grijs voor gratis, die mag wachten op
    een rit in de buurt.

    Gratis heeft twee gezichten. In de regio Amsterdam viel er niets te kiezen en
    geldt de wachttijd van twee weken niet, daarbuiten wel. Dat verschil staat in
    het bijschrift, want het bedrag is in beide gevallen nul.

    Alleen kleuren uit het standaardpalet van Tailwind 2, net als bij _status.

    Verwacht $order. Toont niets zonder keuze: offerte-orders, abonnementen en de
    orders van voor de kolom bestond hebben er nooit een gemaakt, en dan is een
    lege plek eerlijker dan een aanname.
--}}
@php
    $keuzeInRegio = $order->pickup_km !== null
        && $order->pickup_km <= \App\Support\Pricing::freeKm();

    [$keuzeLabel, $keuzeKleur, $keuzeBijschrift] = match ($order->pickup_choice) {
        'spoed'  => ['spoed',  'bg-red-700 text-white',    'binnen 2 werkdagen'],
        'sooner' => ['eerder', 'bg-black text-yellow-400', 'binnen 2 weken'],
        'free'   => ['gratis', 'bg-gray-300 text-black',   $keuzeInRegio ? 'binnen de straal' : 'vanaf 2 weken'],
        default  => [null, null, null],
    };
@endphp
@if ($keuzeLabel && $order->delivery_mode === 'ophaal')
    <span class="inline-block px-1 text-xs font-bold uppercase whitespace-nowrap {{ $keuzeKleur }}">{{ $keuzeLabel }}</span>
    <span class="whitespace-nowrap">{{ $keuzeBijschrift }}</span>
@endif
