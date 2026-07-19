@extends('layouts.app')
@section('title', 'Dagplanning')

{{-- Lijst per dag, bedoeld om mee te rijden. Het kalenderbord op /planning blijft
     voor het verdelen over chauffeurs; dit is het overzicht van wat er staat. --}}

@section('content')
    <div class="flex justify-between items-baseline mb-4 flex-wrap gap-2">
        <h1 class="text-2xl font-black">Dagplanning</h1>
        <div class="flex items-center gap-3 text-sm">
            <a href="{{ route('planning.index') }}" class="underline">kalenderbord ›</a>
            <form method="GET" class="flex items-end gap-2">
                <label>
                    <span class="block text-gray-600 text-xs">Vanaf</span>
                    <input type="date" name="from" value="{{ $from->toDateString() }}" class="border px-2 py-1 text-sm">
                </label>
                <label>
                    <span class="block text-gray-600 text-xs">Dagen</span>
                    <input type="number" name="days" min="1" max="60" value="{{ $days }}" class="border px-2 py-1 text-sm w-20">
                </label>
                <button class="px-3 py-1 text-sm border border-gray-600 hover:bg-gray-200">Toon</button>
            </form>
        </div>
    </div>

    @php
        $dagen = [1 => 'maandag', 2 => 'dinsdag', 3 => 'woensdag', 4 => 'donderdag', 5 => 'vrijdag', 6 => 'zaterdag', 7 => 'zondag'];
        $cursor = $from->copy();
        $totaal = $orders->flatten()->count();
    @endphp

    <p class="text-sm text-gray-600 mb-4">
        {{ $totaal }} rit(ten) van {{ $from->format('d-m-Y') }} t/m {{ $until->format('d-m-Y') }}.
    </p>

    @while ($cursor->lessThanOrEqualTo($until))
        @php
            $key = $cursor->toDateString();
            $dag = $orders->get($key);
            $isWerkdag = \App\Support\WorkingDays::isWorkingDay($cursor);
        @endphp

        @if ($dag || $isWerkdag)
            <section class="mb-5">
                <h2 class="font-black border-b-2 border-black pb-1 mb-2 flex justify-between items-baseline">
                    <span>
                        {{ ucfirst($dagen[$cursor->dayOfWeekIso]) }} {{ $cursor->format('d-m-Y') }}
                        @if ($cursor->isToday()) <span class="bg-yellow-400 text-black text-xs px-2 py-0.5 ml-1">VANDAAG</span> @endif
                    </span>
                    <span class="text-sm font-normal text-gray-500">{{ $dag?->count() ?? 0 }} rit(ten)</span>
                </h2>

                @if (! $dag)
                    <p class="text-sm text-gray-400">
                        @if (! $isWerkdag) Geen werkdag. @else Niets ingepland. @endif
                    </p>
                @else
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs text-gray-600 border-b">
                            <tr>
                                <th class="py-1 w-24">Wat</th>
                                <th class="w-28">Dagdeel</th>
                                <th>Klant</th>
                                <th>Adres</th>
                                <th class="w-32">Chauffeur</th>
                                <th class="w-28">Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dag as $o)
                                @php $brengen = $o->isBezorging(); @endphp
                                <tr class="border-b hover:bg-yellow-50">
                                    <td class="py-1">
                                        <span class="inline-block px-2 py-0.5 text-xs font-bold uppercase {{ $brengen ? 'bg-blue-700 text-white' : 'bg-gray-800 text-white' }}">
                                            {{ $brengen ? 'brengen' : 'ophalen' }}
                                        </span>
                                    </td>
                                    <td>{{ $o->pickup_window ?: 'flexibel' }}</td>
                                    <td>
                                        {{ $o->customer_name }}
                                        @if ($o->customer?->company) <span class="text-xs text-gray-500">— {{ $o->customer->company }}</span>@endif
                                        @if ($o->subscription)
                                            <br><span class="text-xs text-gray-500">abonnement
                                                <a href="{{ route('abonnementen.show', $o->subscription) }}" class="underline font-mono">{{ $o->subscription->order_number }}</a>
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $o->customer_address }}, {{ $o->customer_postcode }} {{ $o->customer_city }}</td>
                                    <td>
                                        @php $driver = $o->bons->first()?->driver; @endphp
                                        {{ $driver?->name ?? '—' }}
                                    </td>
                                    <td class="font-mono text-xs">
                                        <a href="{{ route('orders.show', $o) }}" class="underline">{{ $o->order_number }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        @endif

        @php $cursor->addDay(); @endphp
    @endwhile
@endsection
