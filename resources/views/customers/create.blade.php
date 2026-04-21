@extends('layouts.app')
@section('title', 'Nieuwe klant')

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black">Nieuwe klant</h1>
        <a href="{{ route('customers.index') }}" class="text-sm underline">← klanten</a>
    </div>

    <form method="POST" action="{{ route('customers.store') }}" class="space-y-4">
        @include('customers._form')
        <div class="border-t pt-4 flex gap-3">
            <button class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">Opslaan</button>
            <a href="{{ route('customers.index') }}" class="px-4 py-2 border font-bold uppercase">Annuleren</a>
        </div>
    </form>
@endsection
