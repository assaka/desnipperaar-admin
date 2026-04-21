@extends('layouts.app')
@section('title', $order->order_number)

@section('content')
    <div class="flex justify-between items-start mb-4">
        <div>
            <h1 class="text-2xl font-black font-mono">{{ $order->order_number }}</h1>
            <div class="text-sm text-gray-600">
                Status: <span class="font-bold uppercase">{{ $order->state }}</span>
                @if ($order->pilot) · <span class="bg-yellow-400 text-black px-1">Noord-pilot</span> @endif
            </div>
        </div>
        <a href="{{ route('orders.index') }}" class="text-sm underline">← terug</a>
    </div>

    <section class="grid grid-cols-2 gap-6 mb-6">
        <div>
            <h2 class="font-black mb-2">Klant</h2>
            <div>{{ $order->customer_name }}</div>
            <div><a href="mailto:{{ $order->customer_email }}" class="underline">{{ $order->customer_email }}</a></div>
            <div>{{ $order->customer_phone }}</div>
            <div class="mt-2 text-sm">{{ $order->customer_address }}<br>{{ $order->customer_postcode }} {{ $order->customer_city }}</div>
            @if ($order->customer_reference)
                <div class="mt-2 text-sm">Ref: <span class="font-mono">{{ $order->customer_reference }}</span></div>
            @endif
        </div>
        <div>
            <h2 class="font-black mb-2">Inhoud</h2>
            <div>Dozen: {{ $order->box_count }}</div>
            <div>Rolcontainers: {{ $order->container_count }}</div>
            @if ($order->media_items)
                <div class="mt-2 text-sm"><strong>Gegevensdragers:</strong>
                    <ul class="list-disc ml-5">
                        @foreach ($order->media_items as $item => $qty) <li>{{ $item }}: {{ $qty }}</li> @endforeach
                    </ul>
                </div>
            @endif
            @if ($order->notes) <div class="mt-2 text-sm italic">{{ $order->notes }}</div> @endif
        </div>
    </section>

    <section class="mb-6">
        <h2 class="font-black mb-2">Acties</h2>
        <div class="flex gap-2 flex-wrap">
            @foreach ($availableTransitions as $to)
                <form method="POST" action="{{ route('orders.transition', $order) }}">
                    @csrf
                    <input type="hidden" name="to" value="{{ $to }}">
                    <button class="bg-black text-yellow-400 px-3 py-2 text-xs uppercase font-bold">→ {{ $to }}</button>
                </form>
            @endforeach
        </div>
    </section>

    <section class="mb-6">
        <h2 class="font-black mb-2">Bons</h2>
        @forelse ($order->bons as $bon)
            <div class="border-l-4 border-yellow-400 pl-3 py-2 mb-2">
                <div class="font-mono">
                    <a href="{{ route('bons.show', $bon) }}" class="underline">{{ $bon->bon_number }}</a>
                </div>
                <div class="text-sm">
                    {{ $bon->mode }} · {{ $bon->driver_name_snapshot ?? '—' }} ({{ $bon->driver_license_last4 ?? '—' }}) ·
                    {{ $bon->picked_up_at?->format('Y-m-d H:i') ?? 'nog niet opgehaald' }} ·
                    {{ $bon->weight_kg ? $bon->weight_kg.' kg' : '' }}
                </div>
            </div>
        @empty
            <div class="text-sm text-gray-500">Nog geen bon.</div>
        @endforelse
    </section>

    <section>
        <h2 class="font-black mb-2">Certificaat</h2>
        @if ($order->certificate)
            <a href="{{ route('certificates.show', $order->certificate) }}" class="underline font-mono">
                {{ $order->certificate->certificate_number }}
            </a>
        @else
            <div class="text-sm text-gray-500">Nog niet uitgegeven.</div>
        @endif
    </section>
@endsection
