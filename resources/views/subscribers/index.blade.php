@extends('layouts.app')
@section('title', 'De SnipperDag aanmeldingen')

@section('content')
<div class="flex justify-between items-baseline mb-4">
    <h1 class="text-2xl font-black">De SnipperDag <span class="text-gray-400 font-normal text-lg">({{ $total }} actief)</span></h1>
    <a href="{{ route('subscribers.export') }}" class="bg-black text-yellow-400 px-3 py-1 font-bold text-sm uppercase">Exporteer CSV</a>
</div>

<table class="w-full text-left text-sm mb-6">
    <thead class="border-b">
        <tr>
            <th class="py-2 pr-4">E-mail</th>
            <th class="pr-4">Taal</th>
            <th class="pr-4">Bron</th>
            <th class="pr-4">Campagne</th>
            <th class="pr-4">Aangemeld</th>
            <th class="pr-4">Afgemeld</th>
            <th class="pr-4">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($subscribers as $s)
        <tr class="border-b hover:bg-gray-50 {{ $s->unsubscribed_at ? 'text-gray-400' : '' }}">
            <td class="py-2 pr-4 font-mono">{{ $s->email }}</td>
            <td class="pr-4 uppercase">{{ $s->lang }}</td>
            <td class="pr-4">{{ $s->source ?: '—' }}</td>
            <td class="pr-4">{{ $s->utm_campaign ?: ($s->gclid ? 'google ads' : '—') }}</td>
            <td class="pr-4">{{ $s->created_at?->format('d-m-Y H:i') }}</td>
            <td class="pr-4">{{ $s->unsubscribed_at?->format('d-m-Y H:i') ?: '—' }}</td>
            <td class="pr-4">
                <span class="inline-block px-2 py-0.5 text-xs font-bold uppercase
                    {{ $s->unsubscribed_at ? 'bg-gray-300 text-gray-600' : 'bg-black text-yellow-400' }}">
                    {{ $s->unsubscribed_at ? 'afgemeld' : 'actief' }}
                </span>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="py-8 text-center text-gray-400">Nog geen aanmeldingen.</td></tr>
        @endforelse
    </tbody>
</table>

{{ $subscribers->links() }}
@endsection
