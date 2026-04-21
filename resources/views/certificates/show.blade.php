@extends('layouts.app')
@section('title', $certificate->certificate_number)

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black font-mono">{{ $certificate->certificate_number }}</h1>
        <div class="flex gap-3 text-sm">
            <a href="{{ route('certificates.pdf', $certificate) }}" target="_blank" class="underline">Print / PDF</a>
            <a href="{{ route('orders.show', $certificate->order) }}" class="underline">← order {{ $certificate->order->order_number }}</a>
        </div>
    </div>

    <section class="grid grid-cols-2 gap-6">
        <div>
            <h2 class="font-black">Klant</h2>
            <div>{{ $certificate->order->customer_name }}</div>
            <div>{{ $certificate->order->customer_email }}</div>
        </div>
        <div>
            <h2 class="font-black">Vernietiging</h2>
            <div>Datum: {{ $certificate->destroyed_at?->format('Y-m-d') ?? '—' }}</div>
            <div>Methode: {{ $certificate->destruction_method }}</div>
            <div>Gewicht: {{ $certificate->weight_kg_final ?? '—' }} kg</div>
            <div>Operator: {{ $certificate->operator_name ?? '—' }}</div>
        </div>
    </section>
@endsection
