@extends('layouts.app')
@section('title', 'Herplanningen')

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black">Herplanningsverzoeken</h1>
        <span class="text-sm text-gray-600">{{ $orders->total() }} openstaand</span>
    </div>

    <p class="text-sm text-gray-600 mb-4">Klanten die online een ander ophaalmoment hebben voorgesteld. Bevestig een nieuwe planning op de orderpagina, dan verdwijnt het verzoek hier.</p>

    <table class="w-full text-left">
        <thead class="border-b">
            <tr>
                <th class="py-2">Order#</th>
                <th>Klant</th>
                <th>Huidig</th>
                <th>Voorgesteld</th>
                <th>Toelichting</th>
                <th>Aangevraagd</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr class="border-b hover:bg-orange-50">
                    <td class="py-2 font-mono">
                        <a href="{{ route('orders.show', $order) }}" class="underline">{{ $order->order_number }}</a>
                    </td>
                    <td>{{ $order->customer_name }}</td>
                    <td class="text-sm text-gray-600">
                        @if ($order->pickup_date)
                            {{ $order->pickup_date->format('d-m-Y') }}<br>
                            <span class="text-xs">{{ ucfirst($order->pickup_window ?? 'flexibel') }}</span>
                        @else
                            &mdash;
                        @endif
                    </td>
                    <td class="text-sm font-bold">
                        @if ($order->reschedule_requested_date)
                            {{ $order->reschedule_requested_date->format('d-m-Y') }}<br>
                        @endif
                        <span class="text-xs font-normal">{{ ucfirst($order->reschedule_requested_window ?? '') }}</span>
                    </td>
                    <td class="text-sm italic text-gray-700 max-w-xs">
                        {{ \Illuminate\Support\Str::limit($order->reschedule_notes, 80) }}
                    </td>
                    <td class="text-sm text-gray-600">{{ $order->reschedule_requested_at->format('d-m-Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 text-center text-gray-500">Geen openstaande herplanningsverzoeken.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
