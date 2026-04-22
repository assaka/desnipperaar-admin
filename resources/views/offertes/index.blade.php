@extends('layouts.app')
@section('title', 'Offertes')

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black">Offertes</h1>
        <span class="text-sm text-gray-500">Vrijblijvende offerte-aanvragen op maat</span>
    </div>

    <table class="w-full text-left">
        <thead class="border-b">
            <tr>
                <th class="py-2">Ref</th>
                <th>Klant</th>
                <th>Aangevraagd</th>
                <th>Status</th>
                <th>Bedrag</th>
                <th>Geldig t/m</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($offertes as $offerte)
                @php
                    if ($offerte->quote_accepted_at) {
                        $statusLabel = 'geaccepteerd';
                        $statusClass = 'bg-green-700 text-white';
                    } elseif ($offerte->quote_sent_at) {
                        $statusLabel = $offerte->isQuoteExpired() ? 'verlopen' : 'verzonden';
                        $statusClass = $offerte->isQuoteExpired() ? 'bg-red-700 text-white' : 'bg-yellow-400 text-black';
                    } else {
                        $statusLabel = 'te beantwoorden';
                        $statusClass = 'bg-orange-500 text-white';
                    }
                @endphp
                <tr class="border-b hover:bg-yellow-50">
                    <td class="py-2 font-mono">
                        <a href="{{ route('orders.show', $offerte) }}" class="underline">{{ $offerte->order_number }}</a>
                    </td>
                    <td>
                        {{ $offerte->customer_name }}
                        @if ($offerte->customer?->company) <span class="text-xs text-gray-500">— {{ $offerte->customer->company }}</span>@endif
                    </td>
                    <td class="text-sm">{{ $offerte->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <span class="inline-block px-2 py-1 text-xs font-bold uppercase {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="font-mono">
                        @if ($offerte->quoted_amount_excl_btw)
                            € {{ number_format($offerte->quoted_amount_excl_btw, 2, ',', '.') }}
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="text-sm">
                        {{ $offerte->quote_valid_until?->format('Y-m-d') ?? '—' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 text-center text-gray-500">Nog geen offertes.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $offertes->links() }}</div>
@endsection
