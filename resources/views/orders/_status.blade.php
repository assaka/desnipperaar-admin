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
    [$statusLabel, $statusKleur] = match ($order->stage()) {
        'nieuw'       => ['Nieuw',       'bg-yellow-400 text-black'],
        // Bevestigd zegt dat wij iets hebben bevestigd; ophalen zegt wat er staat
        // te gebeuren, en dat is waar je in de lijst naar zoekt.
        'bevestigd'   => ['Ophalen',     'bg-blue-600 text-white'],
        'opgehaald'   => ['Opgehaald',   'bg-indigo-600 text-white'],
        'vernietigd'  => ['Vernietigd',  'bg-purple-600 text-white'],
        'afgesloten'  => ['Afgesloten',  'bg-gray-500 text-white'],
        'certificate' => ['Certificate', 'bg-black text-yellow-400'],
        'paid'        => ['Paid',        'bg-green-600 text-white'],
        'completed'   => ['Completed',   'bg-green-800 text-white'],
        default       => [$order->stage(), 'bg-gray-300 text-gray-800'],
    };
@endphp
<span class="inline-block px-2 py-1 text-xs font-bold uppercase whitespace-nowrap {{ $statusKleur }}">{{ $statusLabel }}</span>
