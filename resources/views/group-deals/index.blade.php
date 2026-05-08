@extends('layouts.app')
@section('title', 'Groepdeals')

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black">Groepdeals</h1>
    </div>

    @if (session('status'))
        <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-900 px-4 py-2">{{ session('status') }}</div>
    @endif

    <table class="w-full text-left">
        <thead class="border-b">
            <tr>
                <th class="py-2">#</th>
                <th>Stad</th>
                <th>Ophaaldag</th>
                <th>Deelnemers</th>
                <th>Status</th>
                <th>Organisator</th>
                <th>Aangemaakt</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($deals as $deal)
                @php
                    $statusColor = [
                        'draft'     => 'bg-gray-200 text-gray-900',
                        'open'      => 'bg-yellow-300 text-yellow-900',
                        'closed'    => 'bg-blue-200 text-blue-900',
                        'completed' => 'bg-green-200 text-green-900',
                        'cancelled' => 'bg-red-200 text-red-900',
                        'rejected'  => 'bg-red-200 text-red-900',
                    ][$deal->status] ?? 'bg-gray-200';
                @endphp
                <tr class="border-b hover:bg-yellow-50">
                    <td class="py-2 font-mono"><a href="{{ route('group-deals.show', $deal) }}">{{ $deal->id }}</a></td>
                    <td>{{ $deal->city }}</td>
                    <td>{{ $deal->pickup_date->format('Y-m-d') }}</td>
                    <td>{{ $deal->participants_count }} / {{ config('desnipperaar.group_deal.max_joiners') }}</td>
                    <td><span class="inline-block px-2 py-1 text-xs font-bold uppercase {{ $statusColor }}">{{ $deal->status }}</span></td>
                    <td>{{ $deal->organizerParticipant?->customer_name }}</td>
                    <td>{{ $deal->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-6 text-center text-gray-500">Nog geen groepsdeals.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-6">{{ $deals->links() }}</div>
@endsection
