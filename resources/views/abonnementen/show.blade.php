@extends('layouts.app')
@section('title', 'Abonnement '.$order->order_number)

{{-- Eigen pagina voor abonnementen. Ze liepen eerst door orders/show, maar dat is
     een pagina voor één klus: prijs per doos, een ophaaldatum, een bon, een
     certificaat. Een abonnement heeft dat allemaal niet, dus die pagina stond vol
     met uitzonderingen om te verbergen wat niet klopte. Hier staat alleen wat een
     contract echt heeft. De losse ophalingen eronder zijn wél gewone orders en
     houden hun eigen orderpagina. --}}

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black">Abonnement <span class="font-mono">{{ $order->order_number }}</span></h1>
        <a href="{{ route('abonnementen.index') }}" class="text-sm underline">‹ alle abonnementen</a>
    </div>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 mb-4 text-sm">
            @foreach ($errors->all() as $err) <div>{{ $err }}</div> @endforeach
        </div>
    @endif
    @if (session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 mb-4 text-sm">{{ session('status') }}</div>
    @endif

    <section class="mb-6">
        <h2 class="font-black mb-2">Klant</h2>
        @if ($order->customer?->company)
            <div class="font-bold">{{ $order->customer->company }}</div>
        @endif
        <div>
            @if ($order->customer)
                <a href="{{ route('customers.show', $order->customer) }}" class="underline">{{ $order->customer_name }}</a>
            @else
                {{ $order->customer_name }}
            @endif
        </div>
        <div class="text-sm text-gray-700">{{ $order->customer_email }} · {{ $order->customer_phone ?: 'geen telefoon' }}</div>
        <div class="text-sm text-gray-700">
            {{ $order->customer_address ?: '' }} {{ $order->customer_postcode }} {{ $order->customer_city }}
        </div>
    </section>

    <section class="mb-6 bg-blue-50 border-l-4 border-blue-600 p-4">
        <div class="flex justify-between items-baseline mb-3">
            <h2 class="font-black">Abonnement</h2>
            @php $subStatus = $order->subStatus(); @endphp
            <span class="text-xs font-bold uppercase px-2 py-1 {{ match ($subStatus) {
                'actief' => 'bg-green-700 text-white',
                'opgezegd' => 'bg-yellow-400 text-black',
                'beeindigd' => 'bg-gray-500 text-white',
                default => 'bg-orange-500 text-white',
            } }}">
                {{ $subStatus === 'beeindigd' ? 'beëindigd' : $subStatus }}
            </span>
        </div>
        <table class="text-sm">
            <tr><td class="pr-4 text-gray-600">Container</td><td>240 L verzegelde rolcontainer</td></tr>
            <tr><td class="pr-4 text-gray-600">Frequentie</td><td>{{ $order->subFreqLabel() }}</td></tr>
            @if ($order->sub_active_from)
                <tr><td class="pr-4 text-gray-600">Ophaaldag</td><td>
                    <strong>{{ $order->subPickupWeekdayLabel() }}</strong>
                    @if ($order->sub_freq !== '2pw' && $order->isRunning())
                        <form method="POST" action="{{ route('orders.pickup-day', $order) }}" class="inline-flex items-center gap-1 ml-2">
                            @csrf
                            <select name="pickup_weekday" class="border px-1 py-0.5 text-xs">
                                @foreach (\App\Models\Order::PICKUP_WEEKDAYS as $iso => $label)
                                    <option value="{{ $iso }}" @selected($order->subPickupWeekday() == $iso)>{{ ucfirst($label) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="px-2 py-0.5 text-xs border border-gray-600 hover:bg-gray-200">Wijzig</button>
                        </form>
                    @endif
                </td></tr>
            @endif
            <tr><td class="pr-4 text-gray-600">Looptijd</td><td>{{ $order->subTermLabel() }}</td></tr>
            <tr>
                <td class="pr-4 text-gray-600">{{ $order->sub_active_from ? 'Afgesproken prijs' : 'Richtprijs' }}</td>
                <td>
                    @if ($order->sub_price_excl_btw)
                        <strong>€ {{ number_format($order->sub_price_excl_btw, 2, ',', '.') }}</strong>
                        {{ $order->sub_term === 'jaar' ? 'per jaar' : 'per maand' }} excl. btw
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </td>
            </tr>
            @if ($order->sub_active_from)
                <tr><td class="pr-4 text-gray-600">Loopt sinds</td><td>{{ $order->sub_active_from->format('d-m-Y') }}</td></tr>
                <tr><td class="pr-4 text-gray-600">Minimumtermijn</td><td>
                    {{ $order->subMinimumMonths() }} maanden, t/m {{ $order->minimumTermEnd()->format('d-m-Y') }}
                </td></tr>
            @endif
            @if ($order->subRenewalDate())
                <tr><td class="pr-4 text-gray-600">Termijn loopt af</td><td>
                    {{ $order->subRenewalDate()->format('d-m-Y') }}
                    @if ($order->sub_renewal_notified_at)
                        <span class="text-xs text-gray-500">· verlengmail verstuurd {{ $order->sub_renewal_notified_at->format('d-m-Y') }}</span>
                    @else
                        <span class="text-xs text-gray-500">· verlengmail gaat automatisch een maand vooraf</span>
                    @endif
                    <br><span class="text-xs text-gray-600">Daarna maandelijks tegen het Vast-tarief, tenzij de klant verlengt.</span>
                </td></tr>
            @endif
            @if ($order->sub_terminated_at)
                <tr><td class="pr-4 text-gray-600">Opgezegd op</td><td>{{ $order->sub_terminated_at->format('d-m-Y H:i') }}</td></tr>
                <tr><td class="pr-4 text-gray-600">Loopt tot en met</td><td><strong>{{ $order->sub_ends_on->format('d-m-Y') }}</strong></td></tr>
            @endif
            <tr><td class="pr-4 text-gray-600">Laatst gefactureerd</td><td>
                @if ($order->sub_last_invoiced_period)
                    periode vanaf {{ $order->sub_last_invoiced_period->format('d-m-Y') }}
                @else
                    <span class="text-gray-400">nog niet</span>
                @endif
            </td></tr>
        </table>

        @if (! $order->sub_active_from)
            {{-- Aanvraag wacht op goedkeuring. De prijs staat al vast, dus er
                 valt niets te offreren: alleen een ingangsdatum kiezen. --}}
            <div id="goedkeuren" class="mt-3 bg-white border-2 border-green-700 p-4">
                <p class="font-black text-lg mb-1">Aanvraag goedkeuren</p>
                <p class="text-xs text-gray-600 mb-3">
                    De klant krijgt direct een bevestiging met looptijd, frequentie, prijs en ingangsdatum.
                    Vanaf die datum loopt de termijn en start de facturatie, met de eerste maand naar rato.
                </p>
                <form method="POST" action="{{ route('orders.activate-subscription', $order) }}" class="flex items-end gap-2 flex-wrap">
                    @csrf
                    <label class="text-sm">
                        <span class="block text-gray-600 text-xs mb-1">Ingangsdatum</span>
                        <input type="date" name="starts_on" required
                               value="{{ old('starts_on', now()->toDateString()) }}"
                               class="border px-2 py-1 text-sm">
                    </label>
                    @if ($order->sub_freq === '2pw')
                        <span class="text-sm text-gray-700">Ophaaldagen: <strong>maandag en donderdag</strong></span>
                    @else
                        <label class="text-sm">
                            <span class="block text-gray-600 text-xs mb-1">Vaste ophaaldag</span>
                            <select name="pickup_weekday" class="border px-2 py-1 text-sm">
                                @foreach (\App\Models\Order::PICKUP_WEEKDAYS as $iso => $label)
                                    <option value="{{ $iso }}" @selected(old('pickup_weekday', min(now()->dayOfWeekIso, 5)) == $iso)>{{ ucfirst($label) }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    <button type="submit" class="px-4 py-1.5 text-sm font-bold bg-green-700 text-white hover:bg-green-800">
                        Activeer abonnement
                    </button>
                </form>
                @error('starts_on')
                    <p class="text-sm text-red-700 mt-2">{{ $message }}</p>
                @enderror
            </div>
        @endif

        @if ($order->isRunning() && ! $order->sub_terminated_at)
            @php $earliest = $order->earliestTerminationDate(); @endphp
            <p class="text-xs text-gray-600 mt-3">
                Losse ophalingen onder dit abonnement maak je aan als aparte orders.
                Facturen worden elke nacht als concept aangemaakt, vooruit per maand.
            </p>
            <form method="POST" action="{{ route('orders.renew-subscription', $order) }}" class="mt-3 flex items-center gap-2">
                @csrf
                <span class="text-sm text-gray-700">Klant reageerde op de verlengmail:</span>
                <select name="term" class="border px-2 py-1 text-sm">
                    <option value="jaar">nog een jaar vooruit</option>
                    <option value="vast">nog een vaste termijn van 12 maanden</option>
                </select>
                <button type="submit" class="px-3 py-1 text-sm border border-gray-600 hover:bg-gray-200">Termijn vastleggen</button>
            </form>

            <form method="POST" action="{{ route('orders.terminate-subscription', $order) }}" class="mt-3"
                  onsubmit="return confirm('Abonnement {{ $order->order_number }} opzeggen per {{ $earliest->format('d-m-Y') }}?');">
                @csrf
                <button type="submit" class="px-3 py-1 text-sm border border-gray-600 hover:bg-gray-200">
                    Zeg op per {{ $earliest->format('d-m-Y') }}
                </button>
                <span class="text-xs text-gray-600 ml-2">
                    Eerste toegestane einddatum, minimumtermijn meegerekend.
                    @if ($order->sub_term === 'flex' && $earliest->lessThan($order->sub_active_from->copy()->addMonthsNoOverflow(12)))
                        Hierbij komt € {{ number_format((float) config('desnipperaar.subscription.return_cost'), 2, ',', '.') }} retourkosten op de slotfactuur.
                    @endif
                </span>
            </form>
            @php $upcoming = $order->pickups()->whereDate('pickup_date', '>=', now()->toDateString())->limit(8)->get(); @endphp
            <div class="mt-4">
                <p class="font-bold text-sm mb-1">Ingeplande ophalingen</p>
                @if ($upcoming->isEmpty())
                    <p class="text-xs text-gray-600">Nog niets ingepland. De planner draait elke nacht om 02:30 en zet 90 dagen vooruit klaar.</p>
                @else
                    <ul class="text-sm">
                        @foreach ($upcoming as $p)
                            <li>
                                {{ $p->pickup_date->format('d-m-Y') }} ·
                                <a href="{{ route('orders.show', $p) }}" class="underline font-mono text-xs">{{ $p->order_number }}</a>
                                @if ($p->subscription_scheduled_for && ! $p->subscription_scheduled_for->equalTo($p->pickup_date))
                                    <span class="text-xs text-gray-500">verschoven van {{ $p->subscription_scheduled_for->format('d-m-Y') }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @elseif ($order->sub_terminated_at && ! $order->hasEnded())
            <p class="text-sm mt-3 bg-yellow-100 border border-yellow-500 px-3 py-2">
                Opgezegd. Loopt door tot en met {{ $order->sub_ends_on->format('d-m-Y') }} en wordt tot dan gefactureerd.
                Plan het ophalen van de container op of na die datum.
            </p>
        @endif
    </section>

    @if ($order->notes)
        <section class="mb-6 bg-gray-50 border-l-4 border-gray-400 p-4">
            <h2 class="font-black mb-2">Aanvraag</h2>
            <div class="text-sm text-gray-700" style="white-space:pre-line;">{{ $order->notes }}</div>
        </section>
    @endif

    @if ($order->invoices->count())
        <section class="mb-6">
            <h2 class="font-black mb-2">Facturen</h2>
            <table class="w-full text-left text-sm">
                <thead class="border-b"><tr><th class="py-1">Nummer</th><th>Periode</th><th>Bedrag incl.</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($order->invoices->sortByDesc('id') as $inv)
                        <tr class="border-b">
                            <td class="py-1 font-mono"><a href="{{ route('invoices.show', $inv) }}" class="underline">{{ $inv->invoice_number }}</a></td>
                            <td>{{ $inv->period_start?->format('d-m-Y') ?? '—' }} t/m {{ $inv->period_end?->format('d-m-Y') ?? '—' }}</td>
                            <td class="font-mono">€ {{ number_format((float) $inv->amount_incl_btw, 2, ',', '.') }}</td>
                            <td>{{ $inv->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
@endsection
