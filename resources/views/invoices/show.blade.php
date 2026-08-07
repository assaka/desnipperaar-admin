@extends('layouts.app')
@section('title', $invoice->invoice_number)

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <div>
            <h1 class="text-2xl font-black font-mono">{{ $invoice->invoice_number }}</h1>
            <div class="text-sm text-gray-600">
                @if ($invoice->order->isAbonnement())Abonnement: <a href="{{ route('abonnementen.show', $invoice->order_id) }}" class="underline font-mono">{{ $invoice->order->order_number }}</a>@else Order: <a href="{{ route('orders.show', $invoice->order_id) }}" class="underline font-mono">{{ $invoice->order->order_number }}</a>@endif
                @if ($invoice->bon_id) · Bon: <a href="{{ route('bons.show', $invoice->bon_id) }}" class="underline font-mono">{{ $invoice->bon?->bon_number }}</a>@endif
                · Status: <span class="font-bold uppercase">{{ $invoice->status }}</span>
            </div>
        </div>
        <a href="{{ route('invoices.index') }}" class="text-sm underline whitespace-nowrap">← facturen</a>
    </div>

    @if (session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 mb-4 text-sm">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 mb-4 text-sm">
            @foreach ($errors->all() as $err) <div>{{ $err }}</div> @endforeach
        </div>
    @endif

    {{-- Alle acties op deze factuur op één regel, zoals bij een order. Ze stonden
         verspreid over de pagina: versturen bovenaan, crediteren onderaan met een
         eigen formulier ertussen. --}}
    <div x-data="{ proef: false, credit: false }" class="mb-6 pb-4 border-b">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank"
               class="bg-gray-200 text-black px-3 py-1.5 text-xs uppercase font-bold">PDF</a>

            @unless ($invoice->status === \App\Models\Invoice::STATUS_CANCELED)
                <form method="POST" action="{{ route('invoices.mail', $invoice) }}"
                      onsubmit="return confirm('Factuur {{ $invoice->invoice_number }} naar {{ $invoice->customer_email }} sturen?')">
                    @csrf
                    <button class="bg-black text-yellow-400 px-3 py-1.5 text-xs uppercase font-bold">
                        {{ $invoice->sent_at ? 'Resend' : 'Verstuur' }}
                    </button>
                </form>

                {{-- Proefzending laat status en sent_at ongemoeid: dit is geen
                     verzending in de administratie. --}}
                <button type="button" @click="proef = !proef"
                        class="bg-gray-200 text-black px-3 py-1.5 text-xs uppercase font-bold">Proefzending</button>
            @endunless

            @if ($invoice->status === \App\Models\Invoice::STATUS_PAID)
                <span class="bg-green-700 text-white px-3 py-1.5 text-xs uppercase font-bold whitespace-nowrap">
                    Betaald{{ $invoice->paid_at ? ' '.$invoice->paid_at->format('d-m-Y') : '' }}
                </span>
            @elseif ($invoice->status === \App\Models\Invoice::STATUS_SENT)
                <form method="POST" action="{{ route('invoices.mark-paid', $invoice) }}">
                    @csrf
                    <button class="bg-green-700 text-white px-3 py-1.5 text-xs uppercase font-bold">Mark as paid</button>
                </form>
            @endif

            {{-- Crediteren is voor geld dat al is overgemaakt. Staat de factuur nog
                 open, dan is er niets tegen te boeken en pas je de order aan; die
                 rekent de factuur opnieuw. --}}
            @if ($invoice->isCreditNote())
                <span class="text-xs text-gray-500 whitespace-nowrap">creditfactuur</span>
            @elseif ($invoice->isCredited())
                <span class="text-xs text-gray-500 whitespace-nowrap">
                    gecrediteerd met
                    <a href="{{ route('invoices.show', $invoice->creditNote) }}"
                       class="underline font-mono">{{ $invoice->creditNote->invoice_number }}</a>
                </span>
            @elseif ($invoice->status === \App\Models\Invoice::STATUS_PAID)
                <button type="button" @click="credit = true"
                        class="bg-red-700 text-white px-3 py-1.5 text-xs uppercase font-bold">Credit</button>
            @elseif ($invoice->order)
                <span class="text-xs text-gray-500">
                    nog niet betaald, dus
                    <a href="{{ route('orders.show', $invoice->order_id) }}" class="underline">pas de order aan</a>
                </span>
            @endif
        </div>

        {{-- Onder de regel, zodat de knoppen op één rij blijven staan. --}}
        <form method="POST" action="{{ route('invoices.mail', $invoice) }}"
              x-show="proef" x-cloak class="flex items-end gap-2 mt-3">
            @csrf
            <label class="text-sm">
                <span class="block text-xs text-gray-600 mb-1">Proefzending naar</span>
                <input type="email" name="to" required placeholder="naar welk adres"
                       class="border px-2 py-1 text-xs w-64">
            </label>
            <button class="bg-gray-800 text-white px-3 py-1.5 text-xs uppercase font-bold">Stuur proef</button>
            <span class="text-xs text-gray-500">status en verzenddatum blijven ongewijzigd</span>
        </form>

        {{-- Crediteren vraagt om een reden die op de creditfactuur komt, dus een
             modal met een textarea in plaats van een confirm() die niets kan
             opnemen. --}}
        <div x-show="credit" x-cloak
             class="fixed inset-0 z-40 bg-black/60 flex items-start justify-center p-6 overflow-y-auto"
             @click.self="credit = false" @keydown.escape.window="credit = false">
            <div class="bg-white w-full max-w-xl mt-16 p-5 border-2 border-black">
                <h3 class="font-black mb-1">Creditfactuur voor {{ $invoice->invoice_number }}</h3>
                <p class="text-xs text-gray-600 mb-3">
                    Maakt een creditfactuur van
                    − € {{ number_format((float) $invoice->amount_incl_btw, 2, ',', '.') }}
                    die deze factuur tegenboekt. Het origineel blijft staan. De creditfactuur komt als
                    concept klaar, je verstuurt hem daarna zelf.
                </p>
                <form method="POST" action="{{ route('invoices.credit', $invoice) }}">
                    @csrf
                    <label class="block text-sm mb-3">
                        <span class="block text-xs font-bold mb-1">Reden (komt op de creditfactuur)</span>
                        <textarea name="reason" rows="3" maxlength="300"
                                  class="w-full border p-2 text-sm"
                                  placeholder="Bijv. ophaling niet uitgevoerd door DeSnipperaar"></textarea>
                    </label>
                    <div class="flex items-center gap-2">
                        <button class="bg-red-700 text-white px-4 py-2 text-xs uppercase font-bold">Crediteer factuur</button>
                        <button type="button" @click="credit = false"
                                class="bg-gray-200 text-black px-4 py-2 text-xs uppercase font-bold">Annuleren</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
                @php
                    // Een kortingscode is een regel met een negatief bedrag; aantal en
                    // stukprijs zouden dat bedrag alleen herhalen.
                    $isCoupon = fn ($l) => \App\Support\Pricing::isCouponLine($l);
                    $money = fn ($v) => ($v < 0 ? '− € ' : '€ ').number_format(abs($v), 2, ',', '.');
                @endphp
                @foreach ($invoice->lines as $line)
                    <tr class="border-b">
                        <td class="py-2">{{ $line['label'] }}@if ($isCoupon($line) && !empty($line['pct']))<span class="text-gray-500"> ({{ \App\Support\Pricing::formatPercentage($line['pct']) }}% × € {{ number_format($line['base'], 2, ',', '.') }})</span>@endif</td>
                        <td class="text-right font-mono">{{ $isCoupon($line) ? '' : $line['qty'] }}</td>
                        <td class="text-right font-mono">
                            @unless ($isCoupon($line))
                                € {{ number_format($line['unit'], 2, ',', '.') }}
                                @if (!empty($line['was_unit']))
                                    <span class="line-through text-gray-400 ml-1">€ {{ number_format($line['was_unit'], 2, ',', '.') }}</span>
                                @endif
                            @endunless
                        </td>
                        <td class="text-right font-mono font-bold">
                            {{ $money($line['was_subtotal'] ?? $line['subtotal']) }}
                        </td>
                    </tr>
                @endforeach
                @php
                    $subtotalRegular = collect($invoice->lines)->sum(fn ($l) => $l['was_subtotal'] ?? $l['subtotal']);
                    $discount = round($subtotalRegular - (float) $invoice->amount_excl_btw, 2);
                @endphp
                <tr><td colspan="3" class="pt-2 text-gray-600">{{ $discount > 0 ? 'Subtotaal excl. korting' : 'Subtotaal' }} excl. btw</td><td class="text-right font-mono pt-2">€ {{ number_format($subtotalRegular, 2, ',', '.') }}</td></tr>
                @php
                    $discountKennismaking = collect($invoice->lines)->sum(fn ($l) => ($l['unit'] == 0 && isset($l['was_subtotal'])) ? $l['was_subtotal'] : 0);
                    $discountPilot = max(0, round($discount - $discountKennismaking, 2));
                @endphp
                @if ($discountKennismaking > 0)
                    <tr><td colspan="3" class="text-green-700">Korting kennismaking</td><td class="text-right font-mono text-green-700">− € {{ number_format($discountKennismaking, 2, ',', '.') }}</td></tr>
                @endif
                @if ($discountPilot > 0)
                    <tr><td colspan="3" class="text-green-700">Korting Amsterdam-pilot</td><td class="text-right font-mono text-green-700">− € {{ number_format($discountPilot, 2, ',', '.') }}</td></tr>
                @endif
                <tr><td colspan="3" class="text-gray-600">BTW {{ number_format($invoice->vat_rate*100, 0) }}%</td><td class="text-right font-mono">€ {{ number_format($invoice->vat_amount, 2, ',', '.') }}</td></tr>
                <tr class="border-t-2 border-black"><td colspan="3" class="pt-2 font-black">Totaal incl. btw</td><td class="pt-2 text-right font-bold text-lg font-mono">€ {{ number_format($invoice->amount_incl_btw, 2, ',', '.') }}</td></tr>
            </tbody>
        </table>
    </section>

    {{-- Ook hier, en niet alleen op de orderpagina: dit is de plek waar je het
         bedrag ziet dat een korting verandert. --}}
    @if ($invoice->order)
        @include('orders._coupon', ['order' => $invoice->order])
    @endif

    {{-- Alleen toelichting; de knoppen staan bovenaan. --}}
    @if ($invoice->isCreditNote())
        <section class="mt-6 border-t pt-4">
            <p class="text-sm">
                Dit is een <strong>creditfactuur</strong> op
                <a href="{{ route('invoices.show', $invoice->creditsInvoice) }}" class="underline font-mono">{{ $invoice->creditsInvoice?->invoice_number }}</a>@if ($invoice->credit_reason) · {{ $invoice->credit_reason }}@endif.
            </p>
        </section>
    @elseif ($invoice->isCredited())
        @php $note = $invoice->creditNote; @endphp
        <section class="mt-6 border-t pt-4">
            <p class="text-sm">
                Gecrediteerd met
                <a href="{{ route('invoices.show', $note) }}" class="underline font-mono">{{ $note->invoice_number }}</a>
                (€ {{ number_format(abs((float) $note->amount_incl_btw), 2, ',', '.') }})@if ($note->credit_reason) · {{ $note->credit_reason }}@endif.
            </p>
        </section>
    @endif
@endsection
