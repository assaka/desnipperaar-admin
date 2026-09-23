@extends('layouts.app')
@section('title', 'Dashboard')

@php
    $eur = fn ($v) => '€ '.number_format((float) $v, 2, ',', '.');
    $maanden = ['jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec'];
    $maand = fn ($c) => $maanden[$c->month - 1].' '.$c->year;
    $dagen = ['zo','ma','di','wo','do','vr','za'];
@endphp

@section('content')
    <h1 class="text-2xl font-black mb-4">Dashboard</h1>

    <div class="flex justify-between items-baseline mb-2">
        <h2 class="text-lg font-black">Geplande ophalingen</h2>
        <a href="{{ route('planning.index') }}" class="text-sm underline">naar het planbord</a>
    </div>
    @forelse ($pickups as $date => $rows)
        @php $d = \Carbon\Carbon::parse($date); @endphp
        <h3 class="font-bold mt-4 mb-1 {{ $d->isToday() ? 'text-yellow-700' : '' }}">
            {{ $dagen[$d->dayOfWeek] }} {{ $d->format('d-m-Y') }}
            @if ($d->isToday()) <span class="text-xs uppercase">vandaag</span>
            @elseif ($d->isTomorrow()) <span class="text-xs uppercase text-gray-500">morgen</span>
            @endif
            <span class="text-xs font-normal text-gray-500">· {{ $rows->count() }} rit(ten)</span>
        </h3>
        <table class="w-full text-left text-sm">
            <tbody>
                @foreach ($rows as $r)
                    @include('dashboard._pickup_row', ['r' => $r, 'showDate' => false])
                @endforeach
            </tbody>
        </table>
    @empty
        <p class="text-gray-500 py-4">Er staan geen ritten gepland.</p>
    @endforelse

    <div class="mb-10"></div>

    @if ($overdue->isNotEmpty())
        <h2 class="text-lg font-black mb-2 text-red-700">Over datum, nog niet opgehaald</h2>
        <table class="w-full text-left text-sm mb-10">
            <tbody>
                @foreach ($overdue as $r)
                    @include('dashboard._pickup_row', ['r' => $r, 'showDate' => true])
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Kerncijfers. Omzet is het orderbedrag excl. btw, op aanmaakdatum.
         Openstaand is incl. btw, want dat is wat de klant moet overmaken. --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
        <div class="border p-3">
            <div class="text-xs uppercase text-gray-500">Omzet deze maand</div>
            <div class="text-xl font-black">{{ $eur($kpi['this_month']) }}</div>
            <div class="text-xs text-gray-500">vorige maand {{ $eur($kpi['last_month']) }}</div>
        </div>
        <div class="border p-3">
            <div class="text-xs uppercase text-gray-500">Omzet {{ now()->year }}</div>
            <div class="text-xl font-black">{{ $eur($kpi['this_year']) }}</div>
            <div class="text-xs text-gray-500">totaal {{ $eur($kpi['all_time']) }}</div>
        </div>
        <div class="border p-3">
            <div class="text-xs uppercase text-gray-500">Openstaand (incl. btw)</div>
            <div class="text-xl font-black">{{ $eur($kpi['open']) }}</div>
            <a href="{{ route('invoices.index', ['status' => 'sent']) }}" class="text-xs underline">verstuurde facturen</a>
        </div>
        <div class="border p-3">
            <div class="text-xs uppercase text-gray-500">Geplande ritten</div>
            <div class="text-xl font-black">{{ $pickups->flatten(1)->count() }}</div>
            <div class="text-xs text-gray-500">
                {{ $unplanned }} order(s) zonder datum
                @if ($overdue->isNotEmpty())
                    · <span class="text-red-700 font-bold">{{ $overdue->count() }} over datum</span>
                @endif
            </div>
        </div>
    </div>

    <h2 class="text-lg font-black mb-2">Omzet per maand</h2>
    <p class="text-xs text-gray-500 mb-2">
        Orderbedrag op aanmaakdatum van de order, na kortingscode, gefactureerd of niet.
        Zonder open offertes, geannuleerde orders en abonnementen.
        Ontvangen is incl. btw, uit betaalde facturen op betaaldatum.
    </p>
    <table class="w-full text-left text-sm mb-10">
        <thead class="border-b">
            <tr>
                <th class="py-2">Maand</th>
                <th class="text-right">Aangemaakt</th>
                <th class="text-right">Omzet excl.</th>
                <th class="w-1/4 pl-4"></th>
                <th class="text-right">Omzet incl.</th>
                <th class="text-right">Ontvangen</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($months as $key => $m)
                <tr class="border-b {{ $key === now()->format('Y-m') ? 'bg-yellow-50' : '' }}">
                    <td class="py-2 whitespace-nowrap">{{ $maand($m['month']) }}</td>
                    <td class="text-right">{{ $m['count'] ?: '' }}</td>
                    <td class="text-right whitespace-nowrap font-bold">{{ $eur($m['net']) }}</td>
                    <td class="pl-4">
                        <div class="h-3 bg-yellow-400" style="width: {{ max(0, round($m['net'] / $maxNet * 100)) }}%"></div>
                    </td>
                    <td class="text-right whitespace-nowrap">{{ $m['net_incl'] ? $eur($m['net_incl']) : '' }}</td>
                    <td class="text-right whitespace-nowrap text-green-700">{{ $m['received'] ? $eur($m['received']) : '' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="font-bold">
                <td class="py-2">Totaal</td>
                <td class="text-right">{{ collect($months)->sum('count') }}</td>
                <td class="text-right whitespace-nowrap">{{ $eur(collect($months)->sum('net')) }}</td>
                <td></td>
                <td class="text-right whitespace-nowrap">{{ $eur(collect($months)->sum('net_incl')) }}</td>
                <td class="text-right whitespace-nowrap text-green-700">{{ $eur(collect($months)->sum('received')) }}</td>
            </tr>
        </tfoot>
    </table>

@endsection
