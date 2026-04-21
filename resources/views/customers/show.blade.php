@extends('layouts.app')
@section('title', $customer->name)

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <div>
            <h1 class="text-2xl font-black">{{ $customer->name }}</h1>
            <div class="text-sm text-gray-600">{{ $customer->company }}</div>
        </div>
        <div class="flex gap-3 text-sm">
            <a href="{{ route('customers.edit', $customer) }}" class="underline">Bewerk</a>
            <a href="{{ route('customers.index') }}" class="underline">← klanten</a>
        </div>
    </div>

    <section class="grid grid-cols-2 gap-6 mb-6">
        <div>
            <h2 class="font-black mb-2">Contact</h2>
            <div><a href="mailto:{{ $customer->email }}" class="underline">{{ $customer->email }}</a></div>
            <div>{{ $customer->phone }}</div>
            <div class="mt-2 text-sm">{{ $customer->address }}<br>{{ $customer->postcode }} {{ $customer->city }}</div>
        </div>
        <div>
            <h2 class="font-black mb-2">Meta</h2>
            <div class="text-sm">
                @if ($customer->branche) Branche: <strong>{{ $customer->branche }}</strong><br>@endif
                @if ($customer->reference) Ref: <span class="font-mono">{{ $customer->reference }}</span><br>@endif
                @if ($customer->isInPilot()) <span class="bg-yellow-400 text-black px-1">Noord-pilot</span> @endif
            </div>
            @if ($customer->notes)
                <p class="mt-3 text-sm italic">{{ $customer->notes }}</p>
            @endif
        </div>
    </section>

    <section>
        <div class="flex justify-between items-baseline mb-2">
            <h2 class="font-black">Orders</h2>
            <a href="{{ route('orders.create', ['customer' => $customer->id]) }}" class="text-sm underline">+ nieuwe order voor deze klant</a>
        </div>
        <table class="w-full text-left">
            <thead class="border-b">
                <tr><th class="py-2">Order#</th><th>Status</th><th>Modus</th><th>Aangemaakt</th></tr>
            </thead>
            <tbody>
                @forelse ($customer->orders as $order)
                    <tr class="border-b">
                        <td class="py-2 font-mono"><a href="{{ route('orders.show', $order) }}" class="underline">{{ $order->order_number }}</a></td>
                        <td>{{ $order->state }}</td>
                        <td>{{ $order->delivery_mode }}</td>
                        <td>{{ $order->created_at->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-gray-500">Nog geen orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
