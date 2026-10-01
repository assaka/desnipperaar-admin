@extends('layouts.app')
@section('title', 'Open ritten')

@section('content')
<div class="flex justify-between items-baseline mb-4">
    <h1 class="text-2xl font-black">Open ritten</h1>
    <a href="{{ route('route-runs.create') }}" class="bg-black text-yellow-400 px-3 py-1 font-bold text-sm uppercase">+ Nieuwe rit</a>
</div>

<p class="text-sm text-gray-600 mb-4 max-w-3xl">
    Een verre rit die toch al gereden wordt. Zolang hij openstaat krijgt elke klant op /order wiens adres
    hooguit de ingestelde omweg kost gratis ophalen, en de order komt hier onder de rit te staan.
    Inplannen doe je zelf.
</p>

@if (session('status'))
    <div class="bg-green-100 border border-green-400 text-green-800 px-3 py-2 mb-4 text-sm">{{ session('status') }}</div>
@endif

<table class="w-full text-left text-sm mb-8">
    <thead class="border-b">
        <tr>
            <th class="py-2 pr-4">Rit</th>
            <th class="pr-4">Bestemming</th>
            <th class="pr-4">Datum</th>
            <th class="pr-4">Max. omweg</th>
            <th class="pr-4">Ankerorder</th>
            <th class="pr-4">Meerijders</th>
            <th class="pr-4">Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($runs as $r)
        <tr class="border-b hover:bg-gray-50">
            <td class="py-2 pr-4 font-bold">{{ $r->label }}</td>
            <td class="pr-4 font-mono">{{ $r->destination_postcode }}</td>
            <td class="pr-4">{{ $r->run_date ? $r->run_date->format('d-m-Y') : 'nog open' }}</td>
            <td class="pr-4 font-mono">{{ rtrim(rtrim(number_format($r->max_detour_km, 1, ',', ''), '0'), ',') }} km</td>
            <td class="pr-4 font-mono">
                @if ($r->anchorOrder)
                    <a href="{{ route('orders.show', $r->anchor_order_id) }}" class="underline">{{ $r->anchorOrder->order_number }}</a>
                @else
                    —
                @endif
            </td>
            <td class="pr-4 font-mono">{{ $r->orders_count }}</td>
            <td class="pr-4">
                <span class="inline-block px-2 py-0.5 text-xs font-bold uppercase
                    {{ $r->is_open ? 'bg-green-700 text-white' : 'bg-gray-300 text-gray-600' }}">
                    {{ $r->is_open ? 'open' : 'dicht' }}
                </span>
            </td>
            <td class="space-x-3">
                <a href="{{ route('route-runs.edit', $r) }}" class="underline text-sm">Bewerk</a>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="py-8 text-center text-gray-400">Nog geen ritten.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
