{{--
    Het statuslabel van een order, met een eigen kleur per fase zodat je in de
    lijst in één oogopslag ziet waar iets staat.

    Kleuren lopen mee met de reis: geel is nieuw en vraagt aandacht, blauw en
    indigo zijn onderweg, paars is vernietigd, groen is geld binnen en het
    donkerste groen is helemaal klaar. Zwart-geel is het huismerk en houden we
    voor het certificaat.

    Alleen kleuren uit de standaardpalet van Tailwind 2, want de admin haalt de
    volledige CDN-build op en daar zit geen uitgebreid palet in.
--}}
@php
    [$statusLabel, $statusKleur, $statusTitel] = match ($order->stage()) {
        'nieuw'       => ['Nieuw',       'bg-yellow-400 text-black', null],
        // Twee wegen naar hetzelfde punt: de klant koos zelf een moment (gepland)
        // of wij bevestigden er een (bevestigd). In de lijst is dat hetzelfde
        // bericht, namelijk dat er een rit staat, dus hetzelfde label en dezelfde
        // kleur. Bij gepland moet er nog een chauffeur bij; dat verschil in een
        // tint blauw stoppen leest niemand, dus het staat in de tooltip en op de
        // orderpagina zelf.
        'gepland'     => ['Ophalen',     'bg-blue-600 text-white', 'Moment staat vast, chauffeur nog niet toegewezen'],
        'bevestigd'   => ['Ophalen',     'bg-blue-600 text-white', null],
        'opgehaald'   => ['Opgehaald',   'bg-indigo-600 text-white', null],
        'vernietigd'  => ['Vernietigd',  'bg-purple-600 text-white', null],
        'afgesloten'  => ['Afgesloten',  'bg-gray-500 text-white', null],
        'certificate' => ['Certificate', 'bg-black text-yellow-400', null],
        'paid'        => ['Paid',        'bg-green-600 text-white', null],
        'completed'   => ['Completed',   'bg-green-800 text-white', null],
        // Rood, want hier gaat geld de andere kant op. Fel rood zolang wij het
        // nog moeten overmaken, donker als het weg is en er niets meer speelt.
        'credit'      => ['Credit',      'bg-red-700 text-white', 'Teruggeboekt met een creditfactuur, nog niet uitbetaald'],
        'repaid'      => ['Repaid',      'bg-red-900 text-white', 'Creditfactuur uitbetaald aan de klant'],
        default       => [$order->stage(), 'bg-gray-300 text-gray-800', null],
    };
@endphp
<span class="inline-block px-2 py-1 text-xs font-bold uppercase whitespace-nowrap {{ $statusKleur }}"
      @if ($statusTitel) title="{{ $statusTitel }}" @endif>{{ $statusLabel }}</span>
