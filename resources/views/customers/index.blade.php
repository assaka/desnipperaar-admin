@extends('layouts.app')
@section('title', 'Klanten')

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black">Klanten</h1>
        <a href="{{ route('customers.create') }}" class="bg-black text-yellow-400 px-3 py-2 text-sm uppercase font-bold">+ Nieuwe klant</a>
    </div>

    <form method="GET" class="mb-4">
        <input type="search" name="q" value="{{ $q }}" placeholder="Zoek op naam, bedrijf, e-mail of postcode…"
               class="w-full border p-2" autofocus>
    </form>

    <table class="w-full text-left">
        <thead class="border-b">
            <tr>
                <th class="py-2">Naam</th>
                <th>Bedrijf</th>
                <th>E-mail</th>
                <th>Postcode</th>
                <th># orders</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $customer)
                <tr class="border-b hover:bg-yellow-50">
                    <td class="py-2"><a href="{{ route('customers.show', $customer) }}" class="underline">{{ $customer->name }}</a></td>
                    <td>{{ $customer->company }}</td>
                    <td>{{ $customer->email }}</td>
                    <td class="font-mono">{{ $customer->postcode }}</td>
                    <td>{{ $customer->orders_count }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-6 text-center text-gray-500">
                    @if ($q) Geen resultaten voor "{{ $q }}". @else Nog geen klanten. @endif
                </td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $customers->links() }}</div>
@endsection
