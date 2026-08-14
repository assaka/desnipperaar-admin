@extends('layouts.app')
@section('title', $order->order_number)

@section('content')
@php
    // Is er betaald, dan staat de order vast. Adres en aantallen wijzigen zou dan
    // een order veranderen waarvan de rekening al vereffend is; wat er dan nog
    // moet gebeuren is crediteren, niet bijwerken.
    $betaaldeFactuur = $order->invoices->reject->isCreditNote()
        ->firstWhere('status', \App\Models\Invoice::STATUS_PAID);

    // Een geannuleerde order staat net zo goed vast. Er gebeurt niets meer, dus
    // er valt ook niets meer bij te werken of na te sturen.
    $magBewerken = $betaaldeFactuur === null && ! $order->isCanceled();

    // Een verstuurde factuur op een opgehaalde order: de rit is gereden, de
    // rekening is de deur uit en het enige dat er nog moet gebeuren is de
    // betaling afvinken. Dat kon alleen op de factuurpagina.
    $afTeVinkenFactuur = $order->isPickedUp()
        ? $order->invoices->reject->isCreditNote()->firstWhere('status', \App\Models\Invoice::STATUS_SENT)
        : null;

    // Dezelfde voorwaarde als CertificateController::generate(), zodat de knop er
    // alleen staat als hij ook kan slagen. Losser kijken naar "heeft nog geen
    // certificaat" zou hem ook laten zien bij een order waarvan alleen een
    // bezorgrit is getekend, en daar is niets vernietigd om te verklaren.
    $teCertificerenBon = $order->bons()
        ->whereNotNull('picked_up_at')
        ->whereNotIn('mode', \App\Models\Bon::MODES_ZONDER_VERNIETIGING)
        ->whereDoesntHave('certificate')
        ->exists();
@endphp
{{--
    Twee verschillende dingen, en de balk moet ze uit elkaar houden:

      typed = je hebt in het formulier getypt en nog niet opgeslagen. Alleen in de
              browser bekend, verdwijnt bij herladen. Hier valt iets weg te gooien,
              dus alleen hierbij hoort Undo.
      stale = al opgeslagen maar nog niet gemaild, bijvoorbeeld een kortingscode.
              Staat in de database, overleeft een herlaadde pagina. Hier valt niets
              weg te gooien: het is een feit, en de klant moet het horen.

    De balk verschijnt bij beide. Undo alleen bij typed, want Undo op een
    opgeslagen wijziging zou suggereren dat je die kunt terugdraaien.
--}}
<div x-data="{ typed: false, stale: {{ $order->confirmation_stale && ! $order->isCanceled() ? 'true' : 'false' }}, editOpen: false }"
     @order-dirty="typed = true"
     @order-clean="typed = false">

    <div x-show="typed || stale" x-cloak
         class="sticky top-0 z-30 -mx-4 mb-4 px-4 py-3 bg-yellow-400 border-b-2 border-black flex flex-wrap items-center gap-3">
        <span class="font-bold text-sm"
              x-text="typed ? 'Niet opgeslagen wijzigingen.' : 'De klant heeft deze gegevens nog niet.'">De klant heeft deze gegevens nog niet.</span>
        @if ($magBewerken)
            <button type="button"
                    @click="const f = document.getElementById('order-edit-form'); if (f) { f.requestSubmit(); }"
                    class="bg-black text-yellow-400 px-4 py-1.5 text-xs uppercase font-bold">
                Opslaan en mailen
            </button>
            {{-- Gooit weg wat je hebt getypt. Raakt de order niet aan: er is nog
                 niets bewaard. form.reset() stuurt geen input-events, dus de balk
                 springt er niet meteen weer aan. --}}
            <button type="button" x-show="typed" x-cloak
                    @click="const f = document.getElementById('order-edit-form'); if (f) { f.reset(); } typed = false"
                    class="bg-white text-black border-2 border-black px-4 py-1.5 text-xs uppercase font-bold">
                Undo
            </button>
        @endif
        {{-- Resend staat bij de knoppen naast Klant, dus hier alleen de verwijzing
             ernaartoe. Bij een betaalde order is er niets op te slaan en is Resend
             daarboven het enige dat nog kan. --}}
        <span class="text-xs text-gray-800">
            {{ $magBewerken ? 'naar '.$order->customer_email : 'Gebruik Resend hieronder om '.$order->customer_email.' bij te praten.' }}
        </span>
    </div>

    <div class="flex justify-between items-start mb-4">
        <div>
            <h1 class="text-2xl font-black font-mono">{{ $order->order_number }}</h1>
            @if ($order->quote_reference && $order->quote_reference !== $order->order_number)
                <div class="text-xs text-gray-500 font-mono">voortkomend uit offerte {{ $order->quote_reference }}</div>
            @endif
            <div class="text-sm text-gray-600">
                @include('orders._status')
                @if ($order->pilot) · <span class="bg-yellow-400 text-black px-1">Amsterdam-pilot</span> @endif
                @if ($order->first_box_free) · <span class="bg-yellow-400 text-black px-1">Kennismaking</span> @endif
                @if ($order->createdBy)
                    · aangemaakt door <strong>{{ $order->createdBy->name }}</strong>
                @endif
            </div>
        </div>
        <a href="{{ route('orders.index') }}" class="text-sm underline whitespace-nowrap">← terug</a>
    </div>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 mb-4 text-sm">
            @foreach ($errors->all() as $err) <div>{{ $err }}</div> @endforeach
        </div>
    @endif
    @if (session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 mb-4 text-sm">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 mb-4 text-sm">{{ session('error') }}</div>
    @endif

    @if ($order->isCanceled())
        <div class="bg-gray-700 text-white px-3 py-2 mb-4 text-sm">
            <strong class="uppercase">Geannuleerd</strong>
            @if ($order->canceled_at) op {{ $order->canceled_at->format('d-m-Y H:i') }} @endif
            @if ($order->cancel_reason) · {{ $order->cancel_reason }} @endif
            <div class="text-xs text-gray-300 mt-1">
                Deze order gaat niet door. Er wordt niets meer gepland, bijgewerkt of gefactureerd.
                Gaat het toch door, maak dan een nieuwe order aan.
            </div>
        </div>
    @endif

    <section class="mb-6">
        <div>
            <div class="flex items-baseline justify-between gap-3 mb-2">
                <h2 class="font-black">Klant</h2>
                <div class="flex items-center gap-2">
                    @if ($magBewerken)
                        <button type="button" @click="editOpen = !editOpen"
                                class="bg-gray-200 text-black px-3 py-1 text-xs uppercase font-bold whitespace-nowrap"
                                x-text="editOpen ? 'Sluiten' : 'Edit'">Edit</button>
                    @elseif (! $betaaldeFactuur)
                        {{-- Geannuleerd zonder betaalde factuur. Niets te bewerken
                             en niets te crediteren. --}}
                    @elseif ($betaaldeFactuur->isCredited())
                        <span class="text-xs text-gray-500 whitespace-nowrap">
                            betaald en gecrediteerd met
                            <a href="{{ route('invoices.show', $betaaldeFactuur->creditNote) }}"
                               class="underline font-mono">{{ $betaaldeFactuur->creditNote->invoice_number }}</a>
                        </span>
                    @else
                        {{-- Betaald: bijwerken kan niet meer, geld terugboeken wel. --}}
                        <form method="POST" action="{{ route('invoices.credit', $betaaldeFactuur) }}"
                              class="flex items-center gap-2" x-data="{ crediteren: false }"
                              onsubmit="return confirm('Creditfactuur aanmaken voor {{ $betaaldeFactuur->invoice_number }}?')">
                            @csrf
                            <button type="button" @click="crediteren = !crediteren"
                                    class="bg-red-700 text-white px-3 py-1 text-xs uppercase font-bold whitespace-nowrap">Credit</button>
                            <div x-show="crediteren" x-cloak class="flex items-center gap-1">
                                <input type="text" name="reason" maxlength="300"
                                       placeholder="reden op de creditfactuur"
                                       class="border p-1 text-xs w-56">
                                <button class="bg-black text-yellow-400 px-2 py-0.5 text-xs uppercase font-bold">Aanmaken</button>
                            </div>
                        </form>
                    @endif

                    @if ($betaaldeFactuur)
                        <span class="bg-green-700 text-white px-3 py-1 text-xs uppercase font-bold whitespace-nowrap">
                            Paid{{ $betaaldeFactuur->paid_at ? ' '.$betaaldeFactuur->paid_at->format('d-m-Y') : '' }}
                        </span>
                    @endif

                    @if ($teCertificerenBon)
                        {{-- Aanmaken en mailen zit in één actie, dus dat moet in de
                             vraag staan: hierna heeft de klant het certificaat. --}}
                        <form method="POST" action="{{ route('certificates.generate', $order) }}"
                              onsubmit="return confirm('Certificaat aanmaken en direct naar {{ $order->customer_email }} mailen?')">
                            @csrf
                            <button class="bg-gray-800 text-white px-3 py-1 text-xs uppercase font-bold whitespace-nowrap">Certificate</button>
                        </form>
                    @endif

                    @if ($afTeVinkenFactuur)
                        <form method="POST" action="{{ route('invoices.mark-paid', $afTeVinkenFactuur) }}"
                              onsubmit="return confirm('Factuur {{ $afTeVinkenFactuur->invoice_number }} van € {{ number_format((float) $afTeVinkenFactuur->amount_incl_btw, 2, ',', '.') }} als betaald markeren?')">
                            @csrf
                            <button class="bg-green-700 text-white px-3 py-1 text-xs uppercase font-bold whitespace-nowrap">Mark as paid</button>
                        </form>
                    @endif

                    {{-- Resend hoort bij de andere acties op deze order en niet
                         alleen in de balk: de bevestiging opnieuw sturen kan altijd,
                         ook als er niets is gewijzigd. Behalve bij een geannuleerde
                         order, want dan is de bevestiging niet meer waar. --}}
                    @unless ($order->isCanceled())
                        <form method="POST" action="{{ route('orders.resend-confirmation', $order) }}"
                              onsubmit="return confirm('Bevestiging opnieuw sturen naar {{ $order->customer_email }}?')">
                            @csrf
                            <button class="bg-gray-200 text-black px-3 py-1 text-xs uppercase font-bold whitespace-nowrap">Resend</button>
                        </form>
                    @endunless

                    {{-- Annuleren. Zelfde vorm als Credit hierboven: de knop klapt
                         een reden uit, want een geannuleerde order zonder reden is
                         een raadsel zodra de klant er later over belt. De reden gaat
                         mee in de mail, dus schrijf hem voor de klant. --}}
                    @if ($order->canCancel())
                        <form method="POST" action="{{ route('orders.cancel', $order) }}"
                              class="flex items-center gap-2" x-data="{ annuleren: false }"
                              onsubmit="return confirm('Order {{ $order->order_number }} annuleren? Nog niet gereden bons en openstaande facturen vervallen.')">
                            @csrf
                            <button type="button" @click="annuleren = !annuleren"
                                    class="bg-gray-700 text-white px-3 py-1 text-xs uppercase font-bold whitespace-nowrap">Cancel</button>
                            <div x-show="annuleren" x-cloak class="flex items-center gap-2">
                                <input type="text" name="reason" maxlength="300"
                                       placeholder="reden, komt in de mail"
                                       class="border p-1 text-xs w-56">
                                <label class="flex items-center gap-1 text-xs whitespace-nowrap">
                                    <input type="checkbox" name="notify" value="1" checked>
                                    klant mailen
                                </label>
                                <button class="bg-black text-yellow-400 px-2 py-0.5 text-xs uppercase font-bold">Annuleren</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
            @if ($order->customer?->company)
                <div class="font-bold">{{ $order->customer->company }}</div>
            @endif
            <div>
                @if ($order->customer)
                    <a href="{{ route('customers.show', $order->customer) }}" class="underline">{{ $order->customer_name }}</a>
                    @if ($order->customer->reference) <span class="text-xs font-mono text-gray-500"> {{ $order->customer->reference }}</span>@endif
                @else
                    {{ $order->customer_name }}
                @endif
            </div>
            <div class="flex items-center gap-2" x-data="{open:false,addr:'{{ $order->customer_email }}'}">
                <a href="mailto:{{ $order->customer_email }}" class="underline">{{ $order->customer_email }}</a>
                <form method="POST" action="{{ route('orders.mail', $order) }}" class="inline flex items-center gap-2">
                    @csrf
                    <button type="button" @click="open=!open"
                            class="bg-gray-200 text-black px-2 py-0.5 text-xs uppercase font-bold">✉ Mail</button>
                    <div x-show="open" x-cloak class="flex gap-1 items-center">
                        <input type="email" name="to" x-model="addr" class="border p-1 text-xs w-44">
                        <button class="bg-black text-yellow-400 px-2 py-0.5 text-xs uppercase font-bold">Verstuur</button>
                    </div>
                </form>
            </div>
            <div>{{ $order->customer_phone }}</div>
            <div class="mt-2 text-sm">{{ $order->customer_address }}<br>{{ $order->customer_postcode }} {{ $order->customer_city }}</div>
            @if ($order->customer_reference)
                <div class="mt-2 text-sm">Ref: <span class="font-mono">{{ $order->customer_reference }}</span></div>
            @endif

            @if ($magBewerken)
                @include('orders._edit')
            @endif
        </div>
        @if ($order->notes)
            <div class="mt-3 text-sm italic text-gray-700">{{ $order->notes }}</div>
        @endif
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
            @else
                @if ($order->quote_sent_at)
                    <div class="text-sm mb-3">
                        Laatst verzonden op {{ $order->quote_sent_at->format('d-m-Y H:i') }}.
                        @if ($order->quoted_amount_excl_btw !== null && $order->quote_valid_until)
                            Geldig tot {{ $order->quote_valid_until->format('d-m-Y') }}
                            @if ($order->isQuoteExpired()) <span class="text-red-700 font-bold">(VERLOPEN)</span> @endif.
                        @endif
                    </div>
                    @if ($order->quoted_amount_excl_btw !== null)
                        <div class="text-sm mb-3">
                            Bedrag: <strong>€ {{ number_format($order->quoted_amount_excl_btw, 2, ',', '.') }}</strong> excl. btw.
                            @php $publicQuoteUrl = rtrim(config('desnipperaar.public_url'), '/').'/offerte/'.$order->quote_token; @endphp
                            <br>Publieke offertelink: <a href="{{ $publicQuoteUrl }}" target="_blank" class="underline font-mono text-xs">{{ $publicQuoteUrl }}</a>
                        </div>
                        <form method="POST" action="{{ route('orders.quote-valid-until', $order) }}" class="flex flex-wrap items-end gap-2 mb-3">
                            @csrf
                            <label class="text-sm">
                                <span class="block text-gray-600 mb-1">Vervaldatum aanpassen</span>
                                <input type="date" name="quote_valid_until"
                                       value="{{ optional($order->quote_valid_until)->format('Y-m-d') }}"
                                       min="{{ now()->addDay()->format('Y-m-d') }}"
                                       class="border px-2 py-1 text-sm" required>
                            </label>
                            <button type="submit" class="bg-gray-800 text-white px-3 py-1 text-sm font-bold">Opslaan</button>
                            @error('quote_valid_until')
                                <span class="w-full text-red-700 text-xs">{{ $message }}</span>
                            @enderror
                        </form>
                    @endif
                @endif
                @include('orders._quote_form')
            @endif
        </section>
    @endif

    @if ($order->quote_body)
        <section class="mb-6 bg-gray-50 border-l-4 border-yellow-400 p-4">
            <h2 class="font-black mb-2">Toelichting offerte</h2>
            <div class="text-sm text-gray-700" style="white-space:pre-line;">{{ $order->quote_body }}</div>
        </section>
    @endif

    {{-- Wat de klant bij het bestellen zelf koos. Boven het prijsoverzicht, want
         het hoort bij het bedrag: de ophaalkosten in de tabel hieronder komen uit
         deze keuze. Het bepaalt daarnaast hoeveel haast een order heeft, gratis
         mag op een rit in de buurt wachten en spoed niet. --}}
    @if ($order->pickup_choice && $order->delivery_mode === 'ophaal')
        @php $ophaalKosten = (float) ($order->pickup_cost ?? 0) + (float) ($order->pickup_rush_fee ?? 0); @endphp
        <div class="mb-2 text-sm">
            <strong>Klant koos:</strong>
            @include('orders._pickup_choice')
            <span class="text-xs text-gray-600">
                @if ($order->pickup_km !== null) · {{ $order->pickup_km }} km @endif
                · € {{ number_format($ophaalKosten, 2, ',', '.') }} ophaalkosten
            </span>
        </div>
    @endif

    @if (count($quote['lines']))
        <section class="mb-6 bg-gray-50 border-l-4 border-yellow-400 p-4">
            <h2 class="font-black mb-2">Prijsoverzicht
                @if ($actualQuote) <span class="text-xs font-normal text-gray-500">— op basis van bestelling</span> @endif
            </h2>
            <table class="w-full text-sm">
                @foreach ($quote['lines'] as $line)
                    <tr class="border-b">
                        <td class="py-1">{{ $line['label'] }}</td>
                        <td class="text-right font-mono">
                            {{ $line['qty'] }} × € {{ number_format($line['unit'], 2, ',', '.') }}
                            @if (!empty($line['was_unit']))
                                <span class="line-through text-gray-400 ml-1">€ {{ number_format($line['was_unit'], 2, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="text-right font-bold font-mono">
                            € {{ number_format($line['was_subtotal'] ?? $line['subtotal'], 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
                <tr><td class="pt-2 text-gray-600">{{ (!empty($quote['discount']) && $quote['discount'] > 0) ? 'Subtotaal excl. korting' : 'Subtotaal' }}</td><td></td>
                    <td class="text-right font-mono pt-2">€ {{ number_format($quote['subtotal_regular'] ?? $quote['subtotal'], 2, ',', '.') }}</td></tr>
                @if (!empty($quote['discount_kennismaking']) && $quote['discount_kennismaking'] > 0)
                    <tr><td class="text-green-700">Korting kennismaking</td><td></td>
                        <td class="text-right font-mono text-green-700">− € {{ number_format($quote['discount_kennismaking'], 2, ',', '.') }}</td></tr>
                @endif
                @if (!empty($quote['discount_pilot']) && $quote['discount_pilot'] > 0)
                    <tr><td class="text-green-700">Korting Amsterdam-pilot</td><td></td>
                        <td class="text-right font-mono text-green-700">− € {{ number_format($quote['discount_pilot'], 2, ',', '.') }}</td></tr>
                @endif
                @if (!empty($quote['coupon']))
                    <tr>
                        <td class="text-green-700">
                            Kortingscode {{ $quote['coupon']['code'] }}
                            @if (!empty($quote['coupon']['pct']))
                                <span class="text-gray-500">({{ \App\Support\Pricing::formatPercentage($quote['coupon']['pct']) }}% × € {{ number_format($quote['coupon']['base'], 2, ',', '.') }})</span>
                            @endif
                        </td>
                        <td></td>
                        <td class="text-right font-mono text-green-700">− € {{ number_format($quote['coupon']['amount'], 2, ',', '.') }}</td>
                    </tr>
                @endif
                <tr><td class="text-gray-600">BTW 21%</td><td></td>
                    <td class="text-right font-mono">€ {{ number_format($quote['vat'], 2, ',', '.') }}</td></tr>
                <tr class="border-t-2 border-black">
                    <td class="pt-2 font-bold">Totaal incl. BTW</td><td></td>
                    <td class="pt-2 text-right font-bold text-lg font-mono">€ {{ number_format($quote['total'], 2, ',', '.') }}</td>
                </tr>
            </table>
        </section>

        @if ($actualQuote)
            <section class="mb-6 bg-orange-50 border-l-4 border-orange-500 p-4">
                <h2 class="font-black mb-2 flex items-center gap-2">
                    <span style="color:#E67E22;">⚠</span>
                    Gecorrigeerd prijsoverzicht <span class="text-xs font-normal text-gray-700">— op basis van werkelijk opgehaald (dit wordt gefactureerd)</span>
                </h2>
                <table class="w-full text-sm">
                    @foreach ($actualQuote['lines'] as $line)
                        <tr class="border-b">
                            <td class="py-1">{{ $line['label'] }}</td>
                            <td class="text-right font-mono">
                                {{ $line['qty'] }} × € {{ number_format($line['unit'], 2, ',', '.') }}
                                @if (!empty($line['was_unit']))
                                    <span class="line-through text-gray-400 ml-1">€ {{ number_format($line['was_unit'], 2, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="text-right font-bold font-mono">
                                € {{ number_format($line['was_subtotal'] ?? $line['subtotal'], 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                    <tr><td class="pt-2 text-gray-600">{{ (!empty($actualQuote['discount']) && $actualQuote['discount'] > 0) ? 'Subtotaal excl. korting' : 'Subtotaal' }}</td><td></td>
                        <td class="text-right font-mono pt-2">€ {{ number_format($actualQuote['subtotal_regular'] ?? $actualQuote['subtotal'], 2, ',', '.') }}</td></tr>
                    @if (!empty($actualQuote['discount_kennismaking']) && $actualQuote['discount_kennismaking'] > 0)
                        <tr><td class="text-green-700">Korting kennismaking</td><td></td>
                            <td class="text-right font-mono text-green-700">− € {{ number_format($actualQuote['discount_kennismaking'], 2, ',', '.') }}</td></tr>
                    @endif
                    @if (!empty($actualQuote['discount_pilot']) && $actualQuote['discount_pilot'] > 0)
                        <tr><td class="text-green-700">Korting Amsterdam-pilot</td><td></td>
                            <td class="text-right font-mono text-green-700">− € {{ number_format($actualQuote['discount_pilot'], 2, ',', '.') }}</td></tr>
                    @endif
                    <tr><td class="text-gray-600">BTW 21%</td><td></td>
                        <td class="text-right font-mono">€ {{ number_format($actualQuote['vat'], 2, ',', '.') }}</td></tr>
                    <tr class="border-t-2 border-black">
                        <td class="pt-2 font-bold">Totaal incl. BTW</td><td></td>
                        <td class="pt-2 text-right font-bold text-lg font-mono">€ {{ number_format($actualQuote['total'], 2, ',', '.') }}</td>
                    </tr>
                </table>
                @php $delta = $actualQuote['total'] - $quote['total']; @endphp
                <p class="text-sm mt-3">
                    <strong>Verschil:</strong>
                    <span class="font-mono {{ $delta > 0 ? 'text-red-700' : 'text-green-700' }} font-bold">
                        {{ $delta > 0 ? '+' : '' }}€ {{ number_format($delta, 2, ',', '.') }}
                    </span>
                    {{ $delta > 0 ? 'meer dan besteld' : 'minder dan besteld' }}.
                </p>
            </section>
        @endif
    @endif

    @php $firstBon = $order->bons->first(); @endphp

    @include('orders._coupon')

    <section class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4"
             x-data="slotPanel({{ $order->state === 'nieuw' ? 'true' : 'false' }}, '{{ route('orders.slots', $order) }}')">
        <div class="flex justify-between items-baseline mb-3">
            <h2 class="font-black">Geplande ophaling</h2>
            @if ($order->state === 'bevestigd')
                <button type="button" @click="editing = !editing" class="text-xs underline"
                        x-text="editing ? 'Annuleren' : 'Wijzig planning'"></button>
            @endif
        </div>

        {{-- De klant zelf laten kiezen. Met de hand, want de knop staat nog niet
             in de orderbevestiging: zo kunnen wij per klant zien of het werkt
             voordat iedereen hem krijgt. Alleen als er ook echt iets te kiezen
             valt, anders stuurt de knop iemand naar een pagina die zegt dat er al
             een moment staat. --}}
        @if (! $order->isAbonnement()
             && ! $order->pickup_date
             && ! in_array($order->state, ['opgehaald', 'vernietigd', 'afgesloten', 'geannuleerd'], true))
            <div class="mb-3 pb-3 border-b border-yellow-300 flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('orders.send-plan-link', $order) }}"
                      onsubmit="return confirm('Planlink mailen naar {{ $order->customer_email }}?')">
                    @csrf
                    <button class="bg-black text-yellow-400 px-3 py-1 text-xs uppercase font-bold whitespace-nowrap"
                            @disabled(! $order->customer_email || ! $order->public_token)>
                        ✉ Stuur planlink
                    </button>
                </form>
                @if ($order->public_token)
                    <a href="{{ rtrim(config('desnipperaar.public_url'), '/') }}/plan/{{ $order->public_token }}"
                       target="_blank" rel="noopener" class="text-xs underline">bekijk de pagina</a>
                @endif
                @if ($order->pickup_plan_invited_at)
                    <span class="text-xs text-gray-600">
                        verstuurd op {{ $order->pickup_plan_invited_at->format('d-m-Y H:i') }}
                    </span>
                @else
                    <span class="text-xs text-gray-500">nog niet verstuurd</span>
                @endif
                @if (! $order->public_token)
                    <span class="text-xs text-red-700">geen planlink op deze order</span>
                @endif
            </div>
        @endif

        @if ($order->state !== 'nieuw')
            <div x-show="!editing" x-cloak class="text-sm">
                <div><strong>Datum:</strong> {{ $order->pickup_date ? ucfirst($order->pickup_date->locale('nl')->translatedFormat('l d F Y')) : '—' }}
                    @if ($order->pickup_window) ({{ $order->pickup_window }}@switch($order->pickup_window)@case('ochtend') · 08:00–12:00 @break @case('middag') · 12:00–17:00 @break @case('avond') · 17:00–20:00 @break @endswitch)@endif
                </div>
                <div><strong>Chauffeur:</strong> {{ $firstBon?->driver_name_snapshot ?? '—' }}
                    @if ($firstBon?->driver_license_last4) <span class="font-mono text-xs">(****{{ $firstBon->driver_license_last4 }})</span>@endif
                </div>
                    <div class="mt-1 text-xs text-gray-600">Bevestigingsmail is naar de klant verstuurd.</div>
                @if ($order->isCanceled())
                    {{-- De datum blijft staan als geschiedenis, maar er rijdt niemand
                         meer. Zonder deze regel leest dit blok als een afspraak die
                         nog staat. --}}
                    <div class="mt-1 text-xs font-bold text-gray-700">Vervallen door de annulering.</div>
                @endif
                @if ($order->pickup_planned_by_customer_at)
                    <div class="mt-1 text-xs text-gray-600">
                        <span class="bg-green-700 text-white px-1 font-bold uppercase">zelf gepland</span>
                        door de klant op {{ $order->pickup_planned_by_customer_at->format('d-m-Y H:i') }}.
                    </div>
                @endif
            </div>
        @endif

        <form x-show="editing" x-cloak method="POST" action="{{ route('orders.confirm-pickup', $order) }}">
            @csrf
            <div class="grid grid-cols-3 gap-3">
                @php
                    // Rijdt er maar één chauffeur, dan valt er niets te kiezen en
                    // staat hij vast al goed. Zodra er een tweede bij komt is de
                    // keuze weer echt en begint het lijstje weer op "— kies —",
                    // zodat niemand per ongeluk de verkeerde laat staan.
                    $enigeChauffeur = $drivers->count() === 1 ? $drivers->first() : null;
                    $gekozenChauffeur = $firstBon?->driver_id ?? $enigeChauffeur?->id;
                @endphp
                <div>
                    <label class="block text-sm font-bold">Chauffeur *</label>
                    <select name="driver_id" required class="w-full border p-2">
                        @unless ($enigeChauffeur)
                            <option value="">— kies —</option>
                        @endunless
                        @foreach ($drivers as $driver)
                            <option value="{{ $driver->id }}" @selected($gekozenChauffeur === $driver->id)>
                                {{ $driver->name }} (****{{ $driver->license_last4 }})
                                @if (!$driver->signature_path) — geen sig @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                @php
                    $prefillDate   = $order->pickup_date?->format('Y-m-d');
                    $prefillWindow = $order->pickup_window;
                @endphp
                <div>
                    <label class="block text-sm font-bold">Ophaaldatum *</label>
                    <input type="date" name="pickup_date" required min="{{ now()->toDateString() }}"
                           value="{{ $prefillDate }}" class="w-full border p-2" x-ref="date">
                </div>
                <div>
                    <label class="block text-sm font-bold">Dagdeel *</label>
                    <select name="pickup_window" required class="w-full border p-2" x-ref="window">
                        <option value="flexibel" @selected($prefillWindow==='flexibel' || !$prefillWindow)>Flexibel</option>
                        <option value="ochtend"  @selected($prefillWindow==='ochtend')>Ochtend (08:00–12:00)</option>
                        <option value="middag"   @selected($prefillWindow==='middag')>Middag (12:00–17:00)</option>
                        <option value="avond"    @selected($prefillWindow==='avond')>Avond (17:00–20:00)</option>
                        <optgroup label="Specifiek uur">
                            @foreach (range(8, 19) as $hr)
                                @php $slot = sprintf('%02d:00-%02d:00', $hr, $hr + 1); @endphp
                                <option value="{{ $slot }}" @selected($prefillWindow===$slot)>{{ sprintf('%02d:00 – %02d:00', $hr, $hr + 1) }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-bold">Duur (min) <span class="text-xs font-normal text-gray-500">intern — voor planning</span></label>
                    <input type="number" name="duration_minutes" min="5" max="480" step="5"
                           value="{{ old('duration_minutes', $order->duration_minutes ?? 30) }}"
                           class="w-full border p-2" x-ref="duration">
                </div>
            </div>

            {{-- Beschikbare momenten. Rekent per dagdeel uit of de rit er nog bij
                 past en of wij die dag toch al in de buurt zijn; klikken vult de
                 datum en het dagdeel hierboven in. --}}
            <div class="mt-4 bg-white border border-gray-300 p-3">
                <div class="flex justify-between items-baseline">
                    <strong class="text-sm">Beschikbare momenten</strong>
                    <button type="button" @click="load()" :disabled="loading" class="text-xs underline"
                            x-text="loading ? 'Zoeken…' : (result ? 'Opnieuw zoeken' : 'Zoek beschikbare momenten')"></button>
                </div>

                <template x-if="error">
                    <div class="mt-2 text-sm text-red-700" x-text="error"></div>
                </template>

                <template x-if="result">
                    <div class="mt-2">
                        <div class="text-xs text-gray-600">
                            <span x-show="result.depot_km !== null">
                                <strong x-text="result.depot_km"></strong> km van het depot ·
                            </span>
                            <span x-text="result.duration_minutes"></span> min per stop ·
                            <span x-text="result.from"></span> t/m <span x-text="result.until"></span>
                            <span x-show="!result.point" class="text-red-700">
                                · postcode niet gevonden, nabijheid en prijs onbekend
                            </span>
                        </div>

                        <template x-if="!open.length">
                            <div class="mt-2 text-sm text-gray-600">Geen vrij moment binnen de horizon.</div>
                        </template>

                        <div class="mt-2 text-xs font-bold uppercase text-gray-500"
                             x-text="showAll ? 'Alle uurblokken' : 'Wat wij voorstellen'"></div>

                        <div class="mt-1 grid gap-1">
                            <template x-for="s in shown" :key="s.date + s.window">
                                <button type="button" @click="pick(s)" :disabled="!s.available"
                                        class="text-left text-sm border px-2 py-1 flex justify-between items-baseline gap-3"
                                        :class="s.available
                                            ? (s.on_route ? 'border-green-600 bg-green-50 hover:bg-green-100' : 'border-gray-300 hover:bg-yellow-50')
                                            : 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed'">
                                    <span>
                                        <span class="font-bold" x-text="s.weekday + ' ' + s.date"></span>
                                        <span class="font-bold" x-text="'· ' + s.window_label"></span>
                                        <span class="text-xs text-gray-500"
                                              x-text="'· ' + s.day_stops + ' stop(s) die dag, ' + s.free_slots + ' uur vrij'"></span>
                                    </span>
                                    <span class="whitespace-nowrap text-xs">
                                        <template x-if="s.detour_km !== null && s.via_label">
                                            <span :class="s.on_route ? 'text-green-700 font-bold' : 'text-gray-500'"
                                                  x-text="'+' + s.detour_km + ' km omweg via ' + s.via_label"></span>
                                        </template>
                                        <template x-if="s.detour_km !== null && !s.via_label">
                                            <span class="text-gray-400" x-text="'lege dag, eigen rit van ' + s.detour_km + ' km'"></span>
                                        </template>
                                        <span x-show="!s.available" x-text="'· ' + s.reason"></span>
                                    </span>
                                </button>
                            </template>
                        </div>

                        <label class="mt-2 flex items-center gap-2 text-xs text-gray-600">
                            <input type="checkbox" x-model="showAll">
                            Toon alle uurblokken, ook de bezette
                        </label>
                        <p class="text-xs text-gray-500 mt-1">
                            Wij stellen er een handvol voor, gespreid over dagen en gesorteerd op omweg. Op een dag waar al iets staat bieden wij alleen de uren aan die er direct voor of direct na liggen, zodat de dag aaneengesloten volloopt in plaats van met gaten ertussen. Groen = hooguit {{ (int) config('desnipperaar.planning.on_route_detour_km') }} km omweg op de rit van die dag. De omweg is de extra afstand als wij deze stop ertussen schuiven, niet de afstand tot de dichtstbijzijnde stop: iemand die pal op de route ligt kost bijna niets, ook al staat hij ver van de andere stops.
                            De ophaalprijs staat vast op € {{ number_format((float) ($order->pickup_cost ?? 0), 2, ',', '.') }} en is bij het bestellen uit het adres berekend als max(0, km &minus; {{ (int) config('desnipperaar.planning.region_km') }}) &times; € {{ number_format((float) config('desnipperaar.planning.per_km'), 2, ',', '.') }}. Welk uur je kiest verandert daar niets aan.
                            @if ((int) config('desnipperaar.planning.rush_reserve_slots') > 0)
                                <br>Elke dag buiten de eerste {{ (int) config('desnipperaar.planning.rush_days') }} houdt {{ (int) config('desnipperaar.planning.rush_reserve_slots') }} uurblok vrij voor spoed. Die ruimte valt vanzelf vrij zodra de dag dichtbij komt. Hier in de admin kun je er altijd overheen plannen: de velden hierboven kennen deze grens niet.
                            @endif
                        </p>
                    </div>
                </template>
            </div>

            <div class="mt-3">
                <label class="block text-sm font-bold">Opmerking voor de klant <span class="text-xs font-normal text-gray-500">optioneel — komt in de bevestigingsmail, bijv. waarom de gevraagde dag niet kon</span></label>
                <textarea name="pickup_note" rows="3" maxlength="2000" class="w-full border p-2"
                          placeholder="Bijv. de gevraagde zondag was helaas niet beschikbaar, daarom hebben we maandag gepland.">{{ old('pickup_note', $order->pickup_note) }}</textarea>
            </div>
            <button class="mt-3 bg-black text-yellow-400 px-4 py-2 font-bold uppercase">
                {{ $order->state === 'nieuw' ? 'Plan & bevestig aan klant' : 'Planning bijwerken & klant mailen' }}
            </button>
            <p class="text-xs text-gray-600 mt-2">Maakt (of werkt bij) de bon met de chauffeur + ophaalmoment, en stuurt een bevestigingsmail naar de klant.</p>
        </form>
    </section>

    @if ($order->state !== 'nieuw')
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
    @endif

    @if ($order->invoices->count())
        <section class="mb-6">
            <h2 class="font-black mb-2">Factuur</h2>
            @foreach ($order->invoices as $inv)
                @php
                    $statusColor = match($inv->status) {
                        'draft' => 'bg-gray-400 text-white',
                        'sent'  => $inv->due_at->isPast() ? 'bg-red-700 text-white' : 'bg-yellow-400 text-black',
                        'paid'  => 'bg-green-700 text-white',
                        'credit' => 'bg-red-700 text-white',
                        'repaid' => 'bg-red-900 text-white',
                        'canceled' => 'bg-gray-700 text-white',
                        default => 'bg-gray-300 text-gray-700',
                    };
                @endphp
                <div class="border-l-4 border-yellow-400 pl-3 py-2 mb-2 flex justify-between items-baseline">
                    <div>
                        <a href="{{ route('invoices.show', $inv) }}" class="font-mono underline">{{ $inv->invoice_number }}</a>
                        <span class="ml-2 inline-block px-2 py-0.5 text-xs font-bold uppercase {{ $statusColor }}">{{ $inv->status }}</span>
                        <div class="text-sm">
                            € {{ number_format($inv->amount_incl_btw, 2, ',', '.') }} incl. btw ·
                            {{ $inv->issued_at->format('Y-m-d') }} · vervalt {{ $inv->due_at->format('Y-m-d') }}
                            @if ($inv->sent_at) · verzonden {{ $inv->sent_at->format('Y-m-d H:i') }}@endif
                            @if ($inv->paid_at) · {{ $inv->isCreditNote() ? 'terugbetaald' : 'betaald' }} {{ $inv->paid_at->format('Y-m-d') }}@endif
                        </div>
                    </div>
                    {{-- Geen crediteerknop in deze regel. Crediteren staat bij de
                         knoppen naast Klant en op de factuurpagina zelf; hier stond
                         hij een derde keer, en dat is de plek waar je hem het minst
                         verwacht als je alleen de factuur opzoekt. --}}
                    <div class="flex items-center gap-3 whitespace-nowrap">
                        @if ($inv->isCreditNote())
                            <span class="text-xs text-gray-500">creditfactuur</span>
                        @elseif ($inv->isCredited())
                            <span class="text-xs text-gray-500">
                                gecrediteerd met
                                <a href="{{ route('invoices.show', $inv->creditNote) }}"
                                   class="underline font-mono">{{ $inv->creditNote->invoice_number }}</a>
                            </span>
                        @endif
                        <a href="{{ route('invoices.pdf', $inv) }}" target="_blank" class="text-xs underline">PDF →</a>
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    @if ($order->certificate || $hasSignedBon)
        <section>
            <h2 class="font-black mb-2">Certificaat</h2>
            @if ($order->certificate)
                {{-- Zelfde vorm als de factuur hierboven: gele streep, nummer op de
                     eerste regel en de data eronder. --}}
                <div class="border-l-4 border-yellow-400 pl-3 py-2 flex justify-between items-baseline gap-3">
                    <div>
                        <a href="{{ route('certificates.show', $order->certificate) }}" class="font-mono underline">
                            {{ $order->certificate->certificate_number }}
                        </a>
                        @if ($order->certificate->emailed_at)
                            <span class="ml-2 inline-block px-2 py-0.5 text-xs font-bold uppercase bg-green-700 text-white">verzonden</span>
                        @endif
                        <div class="text-sm">
                            @if ($order->certificate->destroyed_at)
                                vernietigd {{ $order->certificate->destroyed_at->format('Y-m-d') }}
                            @endif
                            @if ($order->certificate->weight_kg_final)
                                · {{ number_format((float) $order->certificate->weight_kg_final, 1, ',', '.') }} kg
                            @endif
                            @if ($order->certificate->emailed_at)
                                · verzonden {{ $order->certificate->emailed_at->format('Y-m-d H:i') }}
                            @endif
                        </div>
                    </div>
                    @if (! $order->certificate->emailed_at)
                        <form method="POST" action="{{ route('certificates.mail', $order->certificate) }}">
                            @csrf
                            <button class="bg-black text-yellow-400 px-3 py-1 text-xs uppercase font-bold whitespace-nowrap">Mail naar klant</button>
                        </form>
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('certificates.generate', $order) }}">
                    @csrf
                    <button class="bg-black text-yellow-400 px-3 py-2 text-xs uppercase font-bold">Genereer certificaat</button>
                </form>
            @endif
        </section>
    @endif

    <section class="mt-8">
        <h2 class="font-black mb-2">Berichten</h2>
        @forelse ($order->messages as $m)
            <div class="border-l-4 pl-3 py-2 mb-2 {{ $m->direction === 'in' ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50' }}">
                <div class="text-xs text-gray-500 flex justify-between">
                    <span class="font-bold uppercase">{{ $m->direction === 'in' ? '↓ Van klant' : '↑ Naar klant' }}</span>
                    <span>{{ optional($m->occurred_at)->format('Y-m-d H:i') }}</span>
                </div>
                <div class="text-xs text-gray-500 break-all">{{ $m->from_email }} → {{ $m->to_email }}</div>
                @if ($m->subject)
                    <div class="text-sm font-bold mt-1">{{ $m->subject }}</div>
                @endif
                <div class="text-sm mt-1 whitespace-pre-line text-gray-700">{{ \Illuminate\Support\Str::limit($m->body_text ?: strip_tags($m->body_html ?? ''), 1500) }}</div>
            </div>
        @empty
            <p class="text-sm text-gray-500">Nog geen berichten gelogd.</p>
        @endforelse
    </section>

</div>{{-- einde dirty/editOpen --}}

    <script>
        // Het paneel met beschikbare momenten in de sectie "Geplande ophaling".
        // Zoekt pas op verzoek: de berekening kijkt vier weken vooruit en zoekt
        // ontbrekende coördinaten op, en dat hoort niet te hangen aan elke keer
        // dat iemand een order opent.
        function slotPanel(editing, url) {
            return {
                editing: editing,
                loading: false,
                error: '',
                result: null,
                showAll: false,

                get open() {
                    return this.result ? this.result.slots.filter(s => s.available) : [];
                },

                // Standaard alleen wat wij voorstellen. Vier weken aan uurblokken
                // zijn honderden regels; daar kies je niet uit, daar scroll je
                // doorheen. Het vinkje zet de hele agenda alsnog open, inclusief
                // de bezette uren, want soms wil je juist zien wat er in de weg
                // staat.
                get shown() {
                    if (!this.result) return [];
                    return this.showAll ? this.result.slots : (this.result.best || []);
                },

                async load() {
                    this.loading = true;
                    this.error = '';
                    try {
                        // De duur bepaalt of een rit nog in een dagdeel past, dus
                        // zoeken met het getal dat in het formulier staat en niet
                        // met het opgeslagen getal.
                        const minutes = parseInt(this.$refs.duration?.value || '', 10);
                        const q = minutes > 0 ? ('?duration=' + minutes) : '';
                        const r = await fetch(url + q, { headers: { Accept: 'application/json' } });
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        this.result = await r.json();
                    } catch (e) {
                        this.error = 'Zoeken mislukt: ' + e.message;
                        this.result = null;
                    } finally {
                        this.loading = false;
                    }
                },

                pick(slot) {
                    if (!slot.available) return;
                    this.$refs.date.value = slot.date;
                    this.$refs.window.value = slot.window;
                },
            };
        }
    </script>
@endsection
