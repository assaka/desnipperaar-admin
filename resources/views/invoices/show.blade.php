@extends('layouts.app')
@section('title', $invoice->invoice_number)

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <div>
            <h1 class="text-2xl font-black font-mono">{{ $invoice->invoice_number }}</h1>
            <div class="text-sm text-gray-600">
                Order: <a href="{{ route('orders.show', $invoice->order_id) }}" class="underline font-mono">{{ $invoice->order->order_number }}</a>
                @if ($invoice->bon_id) · Bon: <a href="{{ route('bons.show', $invoice->bon_id) }}" class="underline font-mono">{{ $invoice->bon?->bon_number }}</a>@endif
                · Status: <span class="font-bold uppercase">{{ $invoice->status }}</span>
            </div>
        </div>
        <div class="flex gap-3 text-sm">
            <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="bg-black text-yellow-400 px-3 py-2 text-xs uppercase font-bold">PDF bekijken</a>
            <a href="{{ route('invoices.index') }}" class="underline">← facturen</a>
        </div>
    </div>

    @if (session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 mb-4 text-sm">{{ session('status') }}</div>
    @endif

    <section class="grid grid-cols-2 gap-6 mb-6">
        <div>
            <h2 class="font-black mb-2">Klant</h2>
            @if ($invoice->customer_company) <div class="font-bold">{{ $invoice->customer_company }}</div> @endif
            <div>{{ $invoice->customer_name }}</div>
            <div>{{ $invoice->customer_email }}</div>
            <div class="mt-2 text-sm">{{ $invoice->customer_address }}<br>{{ $invoice->customer_postcode }} {{ $invoice->customer_city }}</div>
        </div>
        <div>
            <h2 class="font-black mb-2">Data</h2>
            <div class="text-sm"><strong>Factuurdatum:</strong> {{ $invoice->issued_at->format('d-m-Y') }}</div>
            <div class="text-sm"><strong>Vervaldatum:</strong> {{ $invoice->due_at->format('d-m-Y') }}
                @if ($invoice->status==='sent' && $invoice->due_at->isPast())
                    <span class="text-red-700 text-xs font-bold">OVERDUE</span>
                @endif
            </div>
            @if ($invoice->sent_at) <div class="text-sm"><strong>Verzonden:</strong> {{ $invoice->sent_at->format('Y-m-d H:i') }}</div> @endif
            @if ($invoice->paid_at) <div class="text-sm"><strong>Betaald:</strong> {{ $invoice->paid_at->format('Y-m-d H:i') }}</div> @endif
        </div>
    </section>

    <section class="mb-6">
        <h2 class="font-black mb-2">Regels</h2>
        <table class="w-full text-left">
            <thead class="border-b">
                <tr><th class="py-2">Omschrijving</th><th class="text-right">Aantal</th><th class="text-right">Prijs</th><th class="text-right">Subtotaal</th></tr>
            </thead>
            <tbody>
                @foreach ($invoice->lines as $line)
                    <tr class="border-b">
                        <td class="py-2">{{ $line['label'] }}</td>
                        <td class="text-right font-mono">{{ $line['qty'] }}</td>
                        <td class="text-right font-mono">
                            € {{ number_format($line['unit'], 2, ',', '.') }}
                            @if (!empty($line['was_unit']))
                                <span class="line-through text-gray-400 ml-1">€ {{ number_format($line['was_unit'], 2, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="text-right font-mono font-bold">
                            € {{ number_format($line['subtotal'], 2, ',', '.') }}
                            @if (!empty($line['was_subtotal']))
                                <span class="line-through text-gray-400 font-normal ml-1 text-xs">€ {{ number_format($line['was_subtotal'], 2, ',', '.') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @php
                    $subtotalRegular = collect($invoice->lines)->sum(fn ($l) => $l['was_subtotal'] ?? $l['subtotal']);
                    $discount = round($subtotalRegular - (float) $invoice->amount_excl_btw, 2);
                @endphp
                <tr><td colspan="3" class="pt-2 text-gray-600">Subtotaal excl. btw</td><td class="text-right font-mono pt-2">€ {{ number_format($subtotalRegular, 2, ',', '.') }}</td></tr>
                @if ($discount > 0)
                    <tr><td colspan="3" class="text-green-700">Korting Noord-pilot</td><td class="text-right font-mono text-green-700">− € {{ number_format($discount, 2, ',', '.') }}</td></tr>
                @endif
                <tr><td colspan="3" class="text-gray-600">BTW {{ number_format($invoice->vat_rate*100, 0) }}%</td><td class="text-right font-mono">€ {{ number_format($invoice->vat_amount, 2, ',', '.') }}</td></tr>
                <tr class="border-t-2 border-black"><td colspan="3" class="pt-2 font-black">Totaal incl. btw</td><td class="pt-2 text-right font-bold text-lg font-mono">€ {{ number_format($invoice->amount_incl_btw, 2, ',', '.') }}</td></tr>
            </tbody>
        </table>
    </section>

    <section class="flex gap-3">
        @if ($invoice->status === 'draft' || $invoice->status === 'sent')
            <form method="POST" action="{{ route('invoices.mail', $invoice) }}">
                @csrf
                <button class="bg-black text-yellow-400 px-4 py-2 text-xs uppercase font-bold">
                    {{ $invoice->sent_at ? '✉ Opnieuw versturen' : '✉ Verzend factuur naar klant' }}
                </button>
            </form>
        @endif
        @if ($invoice->status === 'sent')
            <form method="POST" action="{{ route('invoices.mark-paid', $invoice) }}">
                @csrf
                <button class="bg-green-700 text-white px-4 py-2 text-xs uppercase font-bold">Markeer als betaald</button>
            </form>
        @endif
    </section>
@endsection
