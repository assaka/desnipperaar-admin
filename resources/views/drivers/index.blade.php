@extends('layouts.app')
@section('title', 'Chauffeurs')

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black">Chauffeurs</h1>
        <a href="{{ route('orders.index') }}" class="text-sm underline">← orders</a>
    </div>

    <table class="w-full text-left mb-8">
        <thead class="border-b">
            <tr>
                <th class="py-2">Naam</th>
                <th>Rijbewijs</th>
                <th>VOG t/m</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($drivers as $driver)
                <tr class="border-b">
                    <td class="py-2">{{ $driver->name }}</td>
                    <td class="font-mono">****{{ $driver->license_last4 }}</td>
                    <td class="{{ $driver->isVogExpiringSoon() ? 'text-red-600 font-bold' : '' }}">
                        {{ $driver->vog_valid_until?->format('Y-m-d') ?? '—' }}
                        @if ($driver->isVogExpiringSoon()) ⚠️ verloopt @endif
                    </td>
                    <td>
                        <span class="inline-block px-2 py-1 text-xs font-bold uppercase
                            {{ $driver->active ? 'bg-black text-yellow-400' : 'bg-gray-300 text-gray-700' }}">
                            {{ $driver->active ? 'actief' : 'inactief' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-6 text-center text-gray-500">Nog geen chauffeurs.</td></tr>
            @endforelse
        </tbody>
    </table>

    <section class="border-t pt-6">
        <h2 class="font-black mb-3">Nieuwe chauffeur</h2>
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 mb-3 text-sm">
                @foreach ($errors->all() as $error) <div>{{ $error }}</div> @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('drivers.store') }}" class="grid grid-cols-4 gap-3 max-w-3xl">
            @csrf
            <div class="col-span-2">
                <label class="block text-sm font-bold">Naam</label>
                <input type="text" name="name" required value="{{ old('name') }}" class="w-full border p-2">
            </div>
            <div>
                <label class="block text-sm font-bold">Rijbewijs (laatste 4)</label>
                <input type="text" name="license_last4" required minlength="4" maxlength="4" pattern="[A-Z0-9]{4}"
                       value="{{ old('license_last4') }}" class="w-full border p-2 font-mono uppercase">
            </div>
            <div>
                <label class="block text-sm font-bold">VOG geldig t/m</label>
                <input type="date" name="vog_valid_until" value="{{ old('vog_valid_until') }}" class="w-full border p-2">
            </div>
            <div class="col-span-4">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="active" value="1" checked> Actief
                </label>
            </div>
            <div class="col-span-4">
                <button class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">Toevoegen</button>
            </div>
        </form>
    </section>
@endsection
