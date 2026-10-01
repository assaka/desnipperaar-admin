@extends('layouts.app')
@section('title', $run->label)

@section('content')
<div class="flex justify-between items-baseline mb-4">
    <h1 class="text-2xl font-black">{{ $run->label }}</h1>
    <a href="{{ route('route-runs.index') }}" class="text-sm underline">← ritten</a>
</div>

@if (session('status'))
    <div class="bg-green-100 border border-green-400 text-green-800 px-3 py-2 mb-4 text-sm">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 mb-4 text-sm">
        @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
@endif

<form method="POST" action="{{ route('route-runs.update', $run) }}" class="max-w-2xl grid grid-cols-2 gap-4 mb-8">
    @csrf @method('PUT')
    @include('route-runs._form')
    <div class="col-span-2">
        <button class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">Opslaan</button>
    </div>
</form>

<h2 class="text-lg font-black mb-2">Meerijders ({{ $run->orders->count() }})</h2>
<table class="w-full text-left text-sm mb-8">
    <thead class="border-b">
        <tr>
            <th class="py-2 pr-4">Order</th>
            <th class="pr-4">Klant</th>
            <th class="pr-4">Ophaaladres</th>
            <th class="pr-4">Omweg</th>
            <th class="pr-4">Ophaaldatum</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($run->orders as $o)
            @php
                $loc = $o->pickupLocation();
                $detour = $run->detourKm(\App\Support\Geocoder::forOrder($o));
            @endphp
            <tr class="border-b hover:bg-gray-50">
                <td class="py-2 pr-4 font-mono"><a href="{{ route('orders.show', $o) }}" class="underline">{{ $o->order_number }}</a></td>
                <td class="pr-4">{{ $o->customer_name }}</td>
                <td class="pr-4">{{ $loc['postcode'] ?? '' }} {{ $loc['city'] ?? '' }}</td>
                <td class="pr-4 font-mono">{{ $detour === null ? '—' : number_format($detour, 0, ',', '.').' km' }}</td>
                <td class="pr-4">{{ $o->pickup_date ? $o->pickup_date->format('d-m-Y') : 'nog in te plannen' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="py-6 text-center text-gray-400">Nog niemand.</td></tr>
        @endforelse
    </tbody>
</table>

<form method="POST" action="{{ route('route-runs.destroy', $run) }}"
      onsubmit="return confirm('Rit {{ $run->label }} verwijderen? Meerijdende orders blijven bestaan.')">
    @csrf @method('DELETE')
    <button class="underline text-red-600 text-sm">Rit verwijderen</button>
</form>
@endsection
