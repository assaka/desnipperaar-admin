@extends('layouts.app')
@section('title', 'Abonnementen')

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black">Abonnementen</h1>
        <span class="text-sm text-gray-500">Periodieke rolcontainer, 240 L</span>
    </div>

    @if (session('status'))
        <div class="mb-4 px-3 py-2 bg-green-100 border border-green-700 text-green-900 text-sm">{{ session('status') }}</div>
    @endif

    <table class="w-full text-left">
        <thead class="border-b">
            <tr>
                <th class="py-2">Ref</th>
                <th>Klant</th>
                <th>Aangevraagd</th>
                <th>Status</th>
                <th>Frequentie</th>
                <th>Ophaaldag</th>
                <th>Looptijd</th>
                <th>Prijs</th>
                <th>Eerste ophaling</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($abonnementen as $abo)
                @php
                    $status = $abo->subStatus();
                    $statusLabel = match ($status) {
                        'beeindigd'  => 'beëindigd',
                        default      => $status,
                    };
                    $statusClass = match ($status) {
                        'actief'          => 'bg-green-700 text-white',
                        'geaccepteerd'    => 'bg-green-700 text-white',
                        'opgezegd'        => 'bg-yellow-400 text-black',
                        'verzonden'       => 'bg-yellow-400 text-black',
                        'verlopen'        => 'bg-red-700 text-white',
                        'beeindigd'       => 'bg-gray-500 text-white',
                        default           => 'bg-orange-500 text-white',
                    };
                @endphp
                <tr class="border-b hover:bg-yellow-50 {{ $status === 'beeindigd' ? 'opacity-60' : '' }}">
                    <td class="py-2 font-mono">
                        <a href="{{ route('abonnementen.show', $abo) }}" class="underline">{{ $abo->order_number }}</a>
                    </td>
                    <td>
                        {{ $abo->customer_name }}
                        @if ($abo->customer?->company) <span class="text-xs text-gray-500">— {{ $abo->customer->company }}</span>@endif
                    </td>
                    <td class="text-sm">{{ $abo->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <span class="inline-block px-2 py-1 text-xs font-bold uppercase {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="text-sm">{{ $abo->subFreqLabel() }}</td>
                    <td class="text-sm">{{ $abo->sub_active_from ? $abo->subPickupWeekdayLabel() : '—' }}</td>
                    <td class="text-sm">{{ $abo->subTermLabel() }}</td>
                    <td class="font-mono">
                        @if ($abo->sub_price_excl_btw)
                            € {{ number_format($abo->sub_price_excl_btw, 2, ',', '.') }}
                            <span class="text-xs text-gray-500">{{ $abo->sub_term === 'jaar' ? '/jaar' : '/mnd' }}</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="text-sm">{{ $abo->sub_active_from?->format('Y-m-d') ?? '—' }}</td>
                    <td class="text-right">
                        @unless ($abo->sub_active_from)
                            {{-- Label bewust "openen" en niet "goedkeuren": deze link doet niets
                                 anders dan navigeren. Goedkeuren gebeurt op de orderpagina, met
                                 een ingangsdatum erbij. Een groene knop die belooft te keuren en
                                 alleen doorlinkt laat je denken dat het al gebeurd is. --}}
                            <a href="{{ route('abonnementen.show', $abo) }}#goedkeuren"
                               class="inline-block px-3 py-1 text-xs font-bold bg-green-700 text-white hover:bg-green-800">Openen om goed te keuren ›</a>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="py-6 text-center text-gray-500">Nog geen abonnementen.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $abonnementen->links() }}</div>
@endsection
