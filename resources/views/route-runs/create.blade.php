@extends('layouts.app')
@section('title', 'Nieuwe rit')

@section('content')
<div class="flex justify-between items-baseline mb-4">
    <h1 class="text-2xl font-black">Nieuwe rit</h1>
    <a href="{{ route('route-runs.index') }}" class="text-sm underline">← ritten</a>
</div>

@if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 mb-4 text-sm">
        @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
@endif

<form method="POST" action="{{ route('route-runs.store') }}" class="max-w-2xl grid grid-cols-2 gap-4">
    @csrf
    @include('route-runs._form')
    <div class="col-span-2">
        <button class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">Openzetten</button>
    </div>
</form>
@endsection
