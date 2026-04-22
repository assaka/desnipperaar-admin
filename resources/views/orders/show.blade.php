@extends('layouts.app')
@section('title', $order->order_number)

@section('content')
    <div class="flex justify-between items-start mb-4">
        <div>
            <h1 class="text-2xl font-black font-mono">{{ $order->order_number }}</h1>
            <div class="text-sm text-gray-600">
                Status: <span class="font-bold uppercase">{{ $order->state }}</span>
                @if ($order->pilot) · <span class="bg-yellow-400 text-black px-1">Noord-pilot</span> @endif
                @if ($order->first_box_free) · <span class="bg-yellow-400 text-black px-1">Kennismaking</span> @endif
                @if ($order->createdBy)
                    · aangemaakt door <strong>{{ $order->createdBy->name }}</strong>
                @endif
            </div>
        </div>
        <a href="{{ route('orders.index') }}" class="text-sm underline">← terug</a>
    </div>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 mb-4 text-sm">
            @foreach ($errors->all() as $err) <div>{{ $err }}</div> @endforeach
        </div>
    @endif
    @if (session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 mb-4 text-sm">{{ session('status') }}</div>
    @endif

    <section class="grid grid-cols-2 gap-6 mb-6">
        <div>
            <h2 class="font-black mb-2">Klant</h2>
            <div>
                @if ($order->customer)
                    <a href="{{ route('customers.show', $order->customer) }}" class="underline">{{ $order->customer_name }}</a>
                    @if ($order->customer->reference) <span class="text-xs font-mono text-gray-500"> {{ $order->customer->reference }}</span>@endif
                @else
                    {{ $order->customer_name }}
                @endif
            </div>
            <div><a href="mailto:{{ $order->customer_email }}" class="underline">{{ $order->customer_email }}</a></div>
            <div>{{ $order->customer_phone }}</div>
            <div class="mt-2 text-sm">{{ $order->customer_address }}<br>{{ $order->customer_postcode }} {{ $order->customer_city }}</div>
            @if ($order->customer_reference)
                <div class="mt-2 text-sm">Ref: <span class="font-mono">{{ $order->customer_reference }}</span></div>
            @endif
        </div>
        <div>
            <h2 class="font-black mb-2">Order</h2>
            <div class="text-sm">
                <div><strong>Leveringsmethode:</strong> {{ ucfirst($order->delivery_mode) }}service</div>
                <div><strong>Dozen:</strong> {{ $order->box_count }}</div>
                <div><strong>Rolcontainers:</strong> {{ $order->container_count }}</div>
                @if ($order->pickup_date)
                    <div class="mt-2 p-2 bg-yellow-50 border-l-4 border-yellow-400">
                        <strong>Ophaaldatum:</strong> {{ $order->pickup_date->format('l d F Y') }}
                        @if ($order->pickup_window) ({{ $order->pickup_window }}) @endif
                    </div>
                @endif
                @if ($order->notes)
                    <div class="mt-2 italic">{{ $order->notes }}</div>
                @endif
            </div>
        </div>
    </section>

    @if ($order->type === 'quote')
        <section class="mb-6 bg-orange-50 border-l-4 border-orange-500 p-4">
            <h2 class="font-black mb-3">Offerte op maat</h2>
            @if ($order->quote_accepted_at)
                <div class="bg-green-100 border border-green-400 px-3 py-2 mb-3 text-sm">
                    ✓ Geaccepteerd op {{ $order->quote_accepted_at->format('d-m-Y H:i') }} vanaf IP {{ $order->quote_acceptance_ip }}.
                </div>
                <div class="text-sm">
                    Bedrag: <strong>€ {{ number_format($order->quoted_amount_excl_btw, 2, ',', '.') }}</strong> excl. btw
                    (€ {{ number_format($order->quoted_amount_excl_btw * 1.21, 2, ',', '.') }} incl.)
                </div>
            @elseif ($order->quote_sent_at)
                <div class="text-sm mb-3">
                    Offerte verzonden op {{ $order->quote_sent_at->format('d-m-Y H:i') }}.
                    @if ($order->quote_valid_until)
                        Geldig tot {{ $order->quote_valid_until->format('d-m-Y') }}
                        @if ($order->isQuoteExpired()) <span class="text-red-700 font-bold">(VERLOPEN)</span> @endif.
                    @endif
                </div>
                <div class="text-sm">
                    Bedrag: <strong>€ {{ number_format($order->quoted_amount_excl_btw, 2, ',', '.') }}</strong> excl. btw.
                    <br>Publieke link: <a href="{{ route('quote.show', $order->quote_token) }}" target="_blank" class="underline font-mono text-xs">{{ route('quote.show', $order->quote_token) }}</a>
                </div>
                <details class="mt-3">
                    <summary class="text-xs underline cursor-pointer">Offerte bijwerken en opnieuw versturen</summary>
                    @include('orders._quote_form')
                </details>
            @else
                @include('orders._quote_form')
            @endif
        </section>
    @endif

    @if (count($quote['lines']))
        <section class="mb-6 bg-gray-50 border-l-4 border-yellow-400 p-4">
            <h2 class="font-black mb-2">Prijsoverzicht</h2>
            <table class="w-full text-sm">
                @foreach ($quote['lines'] as $line)
                    <tr class="border-b">
                        <td class="py-1">{{ $line['label'] }}</td>
                        <td class="text-right font-mono">{{ $line['qty'] }} × € {{ number_format($line['unit'], 2, ',', '.') }}</td>
                        <td class="text-right font-bold font-mono">€ {{ number_format($line['subtotal'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr><td class="pt-2 text-gray-600">Subtotaal</td><td></td>
                    <td class="text-right font-mono pt-2">€ {{ number_format($quote['subtotal'], 2, ',', '.') }}</td></tr>
                <tr><td class="text-gray-600">BTW 21%</td><td></td>
                    <td class="text-right font-mono">€ {{ number_format($quote['vat'], 2, ',', '.') }}</td></tr>
                <tr class="border-t-2 border-black">
                    <td class="pt-2 font-bold">Totaal incl. BTW</td><td></td>
                    <td class="pt-2 text-right font-bold text-lg font-mono">€ {{ number_format($quote['total'], 2, ',', '.') }}</td>
                </tr>
            </table>
        </section>
    @endif

    <section class="mb-6">
        <h2 class="font-black mb-2">Acties</h2>
        <div class="flex gap-2 flex-wrap items-center">
            @foreach ($availableTransitions as $to)
                <form method="POST" action="{{ route('orders.transition', $order) }}">
                    @csrf
                    <input type="hidden" name="to" value="{{ $to }}">
                    <button class="bg-black text-yellow-400 px-3 py-2 text-xs uppercase font-bold">→ {{ $to }}</button>
                </form>
            @endforeach

            <form method="POST" action="{{ route('orders.mail', $order) }}" class="flex gap-2 items-center"
                  x-data="{open:false,addr:'{{ $order->customer_email }}'}">
                @csrf
                <button type="button" @click="open=!open"
                        class="bg-gray-200 text-black px-3 py-2 text-xs uppercase font-bold">✉ Mail bevestiging</button>
                <div x-show="open" x-cloak class="flex gap-2 items-center">
                    <input type="email" name="to" x-model="addr" class="border p-1 text-sm" placeholder="{{ $order->customer_email }}">
                    <button class="bg-black text-yellow-400 px-3 py-2 text-xs uppercase font-bold">Verstuur</button>
                </div>
            </form>
        </div>
    </section>

    <section class="mb-6">
        <h2 class="font-black mb-2">Bons</h2>
        @forelse ($order->bons as $bon)
            <div class="border-l-4 border-yellow-400 pl-3 py-2 mb-2">
                <div class="font-mono">
                    <a href="{{ route('bons.show', $bon) }}" class="underline">{{ $bon->bon_number }}</a>
                    @if ($bon->picked_up_at)
                        <span class="ml-2 bg-green-700 text-white px-1 text-xs font-bold uppercase">getekend</span>
                    @else
                        <span class="ml-2 bg-gray-500 text-white px-1 text-xs font-bold uppercase">open</span>
                    @endif
                </div>
                <div class="text-sm">
                    {{ $bon->mode }} · {{ $bon->driver_name_snapshot ?? '— geen chauffeur —' }}
                    @if ($bon->driver_license_last4) (****{{ $bon->driver_license_last4 }})@endif ·
                    {{ $bon->picked_up_at?->format('Y-m-d H:i') ?? 'nog niet getekend' }}
                    @if ($bon->weight_kg) · {{ $bon->weight_kg }} kg @endif
                </div>
            </div>
        @empty
            <div class="text-sm text-gray-500">Nog geen bon.</div>
        @endforelse
    </section>

    <section>
        <h2 class="font-black mb-2">Certificaat</h2>
        @if ($order->certificate)
            <div class="flex gap-3 items-baseline">
                <a href="{{ route('certificates.show', $order->certificate) }}" class="underline font-mono">
                    {{ $order->certificate->certificate_number }}
                </a>
                @if ($order->certificate->emailed_at)
                    <span class="text-xs text-green-700">verzonden {{ $order->certificate->emailed_at->format('Y-m-d H:i') }}</span>
                @else
                    <form method="POST" action="{{ route('certificates.mail', $order->certificate) }}" class="inline">
                        @csrf
                        <button class="bg-black text-yellow-400 px-3 py-1 text-xs uppercase font-bold">Mail certificaat naar klant</button>
                    </form>
                @endif
            </div>
        @elseif (!$hasSignedBon)
            <div class="text-sm text-gray-500 italic">Certificaat kan nog niet worden uitgegeven — de bon is nog niet getekend (geen opgehaalde/afgeleverde goederen).</div>
        @else
            <form method="POST" action="{{ route('certificates.generate', $order) }}">
                @csrf
                <button class="bg-black text-yellow-400 px-3 py-2 text-xs uppercase font-bold">Genereer certificaat</button>
            </form>
        @endif
    </section>
@endsection
