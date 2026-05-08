@extends('layouts.app')
@section('title', 'Groepsdeal '.$deal->slug)

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black">{{ $deal->city }} — {{ $deal->pickup_date->format('l j F Y') }}</h1>
        <span class="inline-block px-2 py-1 text-xs font-bold uppercase bg-black text-yellow-400">{{ $deal->status }}</span>
    </div>

    @if (session('status'))
        <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-900 px-4 py-2">{{ session('status') }}</div>
    @endif

    @php
        $filledBoxes = $deal->participants->sum('box_count');
        $filledContainers = $deal->participants->sum('container_count');
    @endphp
    <table class="text-sm mb-6">
        <tr><td class="pr-4 text-gray-600">Slug:</td><td class="font-mono">{{ $deal->slug }}</td></tr>
        <tr><td class="pr-4 text-gray-600">Doel dozen:</td><td>{{ $filledBoxes }} / {{ $deal->target_box_count }}</td></tr>
        @if ($deal->target_container_count > 0)
            <tr><td class="pr-4 text-gray-600">Doel rolcontainers:</td><td>{{ $filledContainers }} / {{ $deal->target_container_count }}</td></tr>
        @endif
        <tr><td class="pr-4 text-gray-600">Inschrijven sluit:</td><td>{{ $deal->joinCutoffAt()->format('Y-m-d H:i') }}</td></tr>
        <tr><td class="pr-4 text-gray-600">Aangemaakt:</td><td>{{ $deal->created_at->format('Y-m-d H:i') }}</td></tr>
        @if ($deal->approved_at)<tr><td class="pr-4 text-gray-600">Goedgekeurd:</td><td>{{ $deal->approved_at->format('Y-m-d H:i') }}</td></tr>@endif
        @if ($deal->closed_at)<tr><td class="pr-4 text-gray-600">Gesloten:</td><td>{{ $deal->closed_at->format('Y-m-d H:i') }}</td></tr>@endif
        @if ($deal->cancelled_at)<tr><td class="pr-4 text-gray-600">Geannuleerd:</td><td>{{ $deal->cancelled_at->format('Y-m-d H:i') }}</td></tr>@endif
        @if ($deal->cancellation_reason)<tr><td class="pr-4 text-gray-600">Reden:</td><td>{{ $deal->cancellation_reason }}</td></tr>@endif
    </table>

    <div class="flex gap-2 mb-6">
        @if ($deal->status === 'draft')
            <form method="POST" action="{{ route('group-deals.approve', $deal) }}">@csrf
                <button class="bg-green-600 text-white px-3 py-2 text-sm font-bold uppercase">Goedkeuren</button>
            </form>
            <form method="POST" action="{{ route('group-deals.reject', $deal) }}" class="flex gap-2 items-center">@csrf
                <input type="text" name="cancellation_reason" placeholder="Reden voor afwijzing" required class="border px-2 py-1 text-sm" />
                <button class="bg-red-600 text-white px-3 py-2 text-sm font-bold uppercase">Afwijzen</button>
            </form>
        @endif
        @if (in_array($deal->status, ['draft', 'open']))
            <form method="POST" action="{{ route('group-deals.cancel', $deal) }}" class="flex gap-2 items-center">@csrf
                <input type="text" name="cancellation_reason" placeholder="Reden voor annulering" required class="border px-2 py-1 text-sm" />
                <button class="bg-red-600 text-white px-3 py-2 text-sm font-bold uppercase">Annuleren</button>
            </form>
        @endif
        @if ($deal->status === 'open')
            <form method="POST" action="{{ route('group-deals.close', $deal) }}">@csrf
                <button class="bg-blue-600 text-white px-3 py-2 text-sm font-bold uppercase">Nu sluiten + orders aanmaken</button>
            </form>
        @endif
    </div>

    <h2 class="text-xl font-bold mb-2">Deelnemers ({{ $deal->participants->count() }} / {{ config('desnipperaar.group_deal.max_joiners') }})</h2>
    <table class="w-full text-left text-sm">
        <thead class="border-b">
            <tr>
                <th class="py-2">Naam</th>
                <th>Email</th>
                <th>Postcode</th>
                <th>Dozen</th>
                <th>Containers</th>
                <th>Prijs locked (€)</th>
                <th>Order</th>
                <th>Rol</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($deal->participants as $p)
                <tr class="border-b">
                    <td class="py-1">{{ $p->customer_name }}</td>
                    <td>{{ $p->customer_email }}</td>
                    <td>{{ $p->customer_postcode }}</td>
                    <td>{{ $p->box_count }}</td>
                    <td>{{ $p->container_count }}</td>
                    <td>€ {{ number_format($p->price_snapshot['total'] ?? 0, 2, ',', '.') }}</td>
                    <td>
                        @if ($p->order_id)
                            <a class="font-mono underline" href="{{ route('orders.show', $p->order_id) }}">#{{ $p->order_id }}</a>
                        @else
                            <span class="text-gray-500">—</span>
                        @endif
                    </td>
                    <td>
                        @if ($p->id === $deal->organizer_participant_id)
                            <span class="bg-yellow-300 text-yellow-900 px-1 text-xs font-bold uppercase">organisator</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-6 text-center text-gray-500">Nog geen deelnemers.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
