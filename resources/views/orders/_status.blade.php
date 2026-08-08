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
        // Er staat een moment, maar er hangt nog geen chauffeur aan. Zelfde woord
        // als bevestigd, want er staat hetzelfde te gebeuren; lichter blauw zodat
        // je in de lijst ziet dat er nog iets moet.
        'gepland'     => ['Ophalen',     'bg-blue-400 text-black', 'Moment staat vast, chauffeur nog niet toegewezen'],
        // Bevestigd zegt dat wij iets hebben bevestigd; ophalen zegt wat er staat
        // te gebeuren, en dat is waar je in de lijst naar zoekt.
        'bevestigd'   => ['Ophalen',     'bg-blue-600 text-white', null],
        'opgehaald'   => ['Opgehaald',   'bg-indigo-600 text-white', null],
        'vernietigd'  => ['Vernietigd',  'bg-purple-600 text-white', null],
        'afgesloten'  => ['Afgesloten',  'bg-gray-500 text-white', null],
        'certificate' => ['Certificate', 'bg-black text-yellow-400', null],
        'paid'        => ['Paid',        'bg-green-600 text-white', null],
        'completed'   => ['Completed',   'bg-green-800 text-white', null],
        default       => [$order->stage(), 'bg-gray-300 text-gray-800', null],
    };
@endphp
<span class="inline-block px-2 py-1 text-xs font-bold uppercase whitespace-nowrap {{ $statusKleur }}"
      @if ($statusTitel) title="{{ $statusTitel }}" @endif>{{ $statusLabel }}</span>
