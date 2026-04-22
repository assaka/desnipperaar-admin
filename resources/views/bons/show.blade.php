@extends('layouts.app')
@section('title', $bon->bon_number)

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <div>
            <h1 class="text-2xl font-black font-mono">{{ $bon->bon_number }}</h1>
            <div class="text-sm text-gray-600">
                {{ ucfirst($bon->mode) }}service ·
                <a href="{{ route('orders.show', $bon->order) }}" class="underline">{{ $bon->order->order_number }}</a>
                @if ($bon->picked_up_at)
                    · <span class="bg-green-700 text-white px-2 py-0.5 text-xs font-bold uppercase">getekend {{ $bon->picked_up_at->format('Y-m-d H:i') }}</span>
                @else
                    · <span class="bg-gray-600 text-white px-2 py-0.5 text-xs font-bold uppercase">nog niet getekend</span>
                @endif
            </div>
        </div>
        <a href="{{ route('orders.show', $bon->order) }}" class="text-sm underline">← order</a>
    </div>

    @if (session('warning'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-3 py-2 mb-4 text-sm">{{ session('warning') }}</div>
    @endif

    <section class="grid grid-cols-2 gap-6 mb-6">
        <div>
            <h2 class="font-black mb-2">Klant</h2>
            <div>{{ $bon->order->customer_name }}</div>
            <div class="text-sm">{{ $bon->order->customer_address }}<br>{{ $bon->order->customer_postcode }} {{ $bon->order->customer_city }}</div>
        </div>
        <div>
            <h2 class="font-black mb-2">Inhoud (verwacht)</h2>
            <div>Dozen: <strong>{{ $bon->order->box_count }}</strong></div>
            <div>Rolcontainers: <strong>{{ $bon->order->container_count }}</strong></div>
            @if ($bon->order->pickup_date)
                <div class="mt-1 text-sm">Gewenst: {{ $bon->order->pickup_date->format('l d F Y') }} ({{ $bon->order->pickup_window ?? 'flexibel' }})</div>
            @endif
        </div>
    </section>

    <form method="POST" action="{{ route('bons.update', $bon) }}" class="space-y-4">
        @csrf @method('PATCH')

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 text-sm">
                @foreach ($errors->all() as $err) <div>{{ $err }}</div> @endforeach
            </div>
        @endif

        <section>
            <h2 class="font-black mb-3">Chauffeur</h2>
            <select name="driver_id" class="w-full border p-2">
                <option value="">— nog geen chauffeur —</option>
                @foreach ($drivers as $driver)
                    <option value="{{ $driver->id }}" {{ $bon->driver_id == $driver->id ? 'selected' : '' }}>
                        {{ $driver->name }} (****{{ $driver->license_last4 }})
                    </option>
                @endforeach
            </select>
        </section>

        <section>
            <h2 class="font-black mb-3">Aanlevering</h2>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold">Datum + tijd opgehaald</label>
                    <input type="datetime-local" name="picked_up_at"
                           value="{{ old('picked_up_at', $bon->picked_up_at?->format('Y-m-d\\TH:i')) }}"
                           class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Gewicht (kg)</label>
                    <input type="number" step="0.1" name="weight_kg" value="{{ old('weight_kg', $bon->weight_kg) }}" class="w-full border p-2">
                </div>
            </div>
        </section>

        <section>
            <h2 class="font-black mb-3">Zegelnummers</h2>
            <p class="text-xs text-gray-500 mb-2">Eén per regel of gescheiden door komma's.</p>
            <textarea name="seals" rows="4" class="w-full border p-2 font-mono"
                      placeholder="SEAL-000123&#10;SEAL-000124">{{ old('seals', $bon->seals->pluck('seal_number')->implode("\n")) }}</textarea>
        </section>

        <section>
            <h2 class="font-black mb-3">Notities</h2>
            <textarea name="notes" rows="3" class="w-full border p-2">{{ old('notes', $bon->notes) }}</textarea>
        </section>

        <div class="border-t pt-4 flex gap-3">
            <button class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">Bon bijwerken</button>
            @if ($bon->picked_up_at)
                <a href="{{ route('bons.pdf', $bon) }}" target="_blank" class="px-4 py-2 border font-bold uppercase underline">Print PDF</a>
            @endif
        </div>
    </form>
@endsection
