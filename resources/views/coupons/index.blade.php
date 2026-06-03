@extends('layouts.app')
@section('title', 'Coupon codes')

@section('content')
<div class="flex justify-between items-baseline mb-4">
    <h1 class="text-2xl font-black">Coupon codes</h1>
    <a href="{{ route('coupons.create') }}" class="bg-black text-yellow-400 px-3 py-1 font-bold text-sm uppercase">+ Nieuwe coupon</a>
</div>

@if (session('status'))
    <div class="bg-green-100 border border-green-400 text-green-800 px-3 py-2 mb-4 text-sm">{{ session('status') }}</div>
@endif

<table class="w-full text-left text-sm mb-8">
    <thead class="border-b">
        <tr>
            <th class="py-2 pr-4">Code</th>
            <th class="pr-4">Type</th>
            <th class="pr-4">Waarde</th>
            <th class="pr-4">Min. order</th>
            <th class="pr-4">Gebruik</th>
            <th class="pr-4">Verloopt</th>
            <th class="pr-4">Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($coupons as $c)
        <tr class="border-b hover:bg-gray-50">
            <td class="py-2 pr-4 font-mono font-bold">{{ $c->code }}</td>
            <td class="pr-4">{{ $c->type === 'percentage' ? 'Percentage' : 'Vast' }}</td>
            <td class="pr-4 font-mono">
                @if ($c->type === 'percentage') {{ $c->value }}%
                @else € {{ number_format($c->value, 2, ',', '.') }}
                @endif
            </td>
            <td class="pr-4">{{ $c->min_order_amount ? '€ ' . number_format($c->min_order_amount, 2, ',', '.') : '—' }}</td>
            <td class="pr-4">{{ $c->times_used }}{{ $c->max_uses ? ' / ' . $c->max_uses : '' }}</td>
            <td class="pr-4 {{ ($c->expires_at && $c->expires_at->isPast()) ? 'text-red-600 font-bold' : '' }}">
                {{ $c->expires_at ? $c->expires_at->format('d-m-Y H:i') : 'Nooit' }}
            </td>
            <td class="pr-4">
                <span class="inline-block px-2 py-0.5 text-xs font-bold uppercase
                    {{ $c->is_active ? 'bg-black text-yellow-400' : 'bg-gray-300 text-gray-600' }}">
                    {{ $c->is_active ? 'actief' : 'inactief' }}
                </span>
            </td>
            <td class="space-x-3">
                <a href="{{ route('coupons.edit', $c) }}" class="underline text-sm">Bewerk</a>
                <form method="POST" action="{{ route('coupons.destroy', $c) }}" class="inline"
                      onsubmit="return confirm('Coupon {{ $c->code }} verwijderen?')">
                    @csrf @method('DELETE')
                    <button class="underline text-red-600 text-sm">Verwijder</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="py-8 text-center text-gray-400">Nog geen coupons.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
