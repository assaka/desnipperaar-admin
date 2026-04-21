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
                <th>Status</th>
                <th>Aangemaakt</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr class="border-b hover:bg-yellow-50">
                    <td class="py-2 font-mono"><a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a></td>
                    <td>{{ $order->customer_name }}</td>
                    <td>{{ $order->customer_postcode }}</td>
                    <td>{{ $order->delivery_mode }}</td>
                    <td>
                        <span class="inline-block px-2 py-1 text-xs font-bold bg-black text-yellow-400 uppercase">
                            {{ $order->state }}
                        </span>
                    </td>
                    <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 text-center text-gray-500">Nog geen orders.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
