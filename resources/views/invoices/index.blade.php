@extends('layouts.app')
@section('title', 'Facturen')

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black">Facturen</h1>
        <form method="GET" class="text-sm">
            <select name="status" onchange="this.form.submit()" class="border p-1">
                <option value="">Alle statussen</option>
                <option value="draft"    @selected($q==='draft')>Concept</option>
                <option value="sent"     @selected($q==='sent')>Verzonden</option>
                <option value="paid"     @selected($q==='paid')>Betaald</option>
                <option value="canceled" @selected($q==='canceled')>Geannuleerd</option>
            </select>
        </form>
    </div>

    <table class="w-full text-left">
        <thead class="border-b">
            <tr>
                <th class="py-2">Factuurnr</th>
                <th>Klant</th>
                <th>Order</th>
                <th>Datum</th>
                <th>Vervaldatum</th>
                <th class="text-right">Bedrag</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $inv)
                @php
                    $statusColor = match($inv->status) {
                        'draft' => 'bg-gray-400 text-white',
                        'sent'  => $inv->due_at->isPast() ? 'bg-red-700 text-white' : 'bg-yellow-400 text-black',
                        'paid'  => 'bg-green-700 text-white',
                        'canceled' => 'bg-gray-700 text-white',
                        default => 'bg-gray-300 text-gray-700',
                    };
                @endphp
                <tr class="border-b hover:bg-yellow-50">
                    <td class="py-2 font-mono"><a href="{{ route('invoices.show', $inv) }}" class="underline">{{ $inv->invoice_number }}</a></td>
                    <td>{{ $inv->customer_company ?: $inv->customer_name }}</td>
                    <td><a href="{{ route('orders.show', $inv->order_id) }}" class="underline font-mono text-xs">{{ $inv->order->order_number }}</a></td>
                    <td class="text-sm">{{ $inv->issued_at->format('Y-m-d') }}</td>
                    <td class="text-sm">
                        {{ $inv->due_at->format('Y-m-d') }}
                        @if ($inv->status==='sent' && $inv->due_at->isPast())
                            <span class="text-red-700 text-xs font-bold">OVERDUE</span>
                        @endif
                    </td>
                    <td class="text-right font-mono font-bold">€ {{ number_format($inv->amount_incl_btw, 2, ',', '.') }}</td>
                    <td>
                        <span class="inline-block px-2 py-1 text-xs font-bold uppercase {{ $statusColor }}">{{ $inv->status }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-6 text-center text-gray-500">Geen facturen.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $invoices->links() }}</div>
@endsection
