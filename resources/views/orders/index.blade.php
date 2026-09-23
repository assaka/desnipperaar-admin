@extends('layouts.app')
@section('title', 'Orders')

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black">Orders</h1>
        <a href="{{ route('orders.create') }}" class="bg-black text-yellow-400 px-3 py-2 text-sm uppercase font-bold">+ Nieuwe order</a>
    </div>

    <table class="w-full text-left">
        <thead class="border-b">
            <tr>
                <th class="py-2">Order#</th>
                <th>Klant</th>
                <th>Postcode</th>
                <th>Modus</th>
                <th>Keuze</th>
                <th>Ophaaldatum</th>
                <th class="text-right pr-4">Omzet excl.</th>
                <th>Status</th>
                <th>Aangemaakt</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr class="border-b hover:bg-yellow-50">
                    <td class="py-2 font-mono">
                        <a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a>
                        @if ($order->type === 'quote')
                            <span class="ml-1 bg-orange-200 text-orange-900 px-1 text-xs font-bold uppercase">offerte</span>
                        @endif
                    </td>
                    <td>{{ $order->customer_name }}</td>
                    {{-- De postcode waar de wagen heen moet, want daar plan je op.
                         Een afwijkend ophaaladres krijgt een merkje, anders lijkt
                         het in de lijst een gewone order op het klantadres. --}}
                    <td>
                        {{ $order->pickupLocation()['postcode'] }}
                        @if ($order->hasSeparatePickupAddress())
                            <span class="ml-1 bg-yellow-200 text-yellow-900 px-1 text-xs font-bold uppercase" title="Ophalen op een ander adres dan het klantadres">ophaal</span>
                        @endif
                    </td>
                    <td>{{ $order->delivery_mode }}</td>
                    {{-- De ophaalsnelheid hoort naast de ophaaldatum: samen laten
                         ze zien of een order haast heeft en of er al iets staat.
                         Spoed zonder datum is wat je in deze lijst zoekt. --}}
                    <td class="text-sm">
                        @if ($order->pickup_choice)
                            @include('orders._pickup_choice')
                        @else
                            <span class="text-gray-400">&mdash;</span>
                        @endif
                    </td>
                    <td>
                        @if ($order->pickup_date)
                            {{ $order->pickup_date->format('d-m-Y') }}
                            @if ($order->pickup_window)
                                <span class="block text-xs text-gray-500">{{ $order->pickup_window }}</span>
                            @endif
                        @else
                            <span class="text-gray-400">nog niet gepland</span>
                        @endif
                    </td>
                    {{-- Netto gefactureerd. Grijs is nog geen omzet: een concept
                         of het geoffreerde bedrag, voor er een factuur is. --}}
                    @php $omzet = $order->revenue(); @endphp
                    <td class="text-right pr-4 whitespace-nowrap">
                        @if ($omzet['amount'] === null)
                            <span class="text-gray-400">&mdash;</span>
                        @elseif ($omzet['status'] === 'invoiced')
                            <span class="{{ $omzet['amount'] < 0 ? 'text-red-700' : '' }}">€ {{ number_format($omzet['amount'], 2, ',', '.') }}</span>
                        @else
                            <span class="text-gray-400" title="{{ $omzet['status'] === 'draft' ? 'Conceptfactuur, nog niet verstuurd' : 'Geoffreerd bedrag, nog geen factuur' }}">€ {{ number_format($omzet['amount'], 2, ',', '.') }}</span>
                        @endif
                    </td>
                    <td>@include('orders._status')</td>
                    <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="py-6 text-center text-gray-500">Nog geen orders.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="mt-2 text-sm text-right">
        Gefactureerd op deze pagina:
        <strong>€ {{ number_format($orders->getCollection()->sum(fn ($o) => $o->revenue()['status'] === 'invoiced' ? $o->revenue()['amount'] : 0), 2, ',', '.') }}</strong>
        excl. btw · <a href="{{ route('dashboard') }}" class="underline">omzet per maand</a>
    </p>

    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
