@extends('layouts.app')
@section('title', 'Nieuwe coupon')

@section('content')
<div class="flex justify-between items-baseline mb-4">
    <h1 class="text-2xl font-black">Nieuwe coupon</h1>
    <a href="{{ route('coupons.index') }}" class="text-sm underline">← coupons</a>
</div>

@if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 mb-4 text-sm">
        @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
@endif

<form method="POST" action="{{ route('coupons.store') }}" class="max-w-2xl grid grid-cols-2 gap-4">
    @csrf
    @include('coupons._form')
    <div class="col-span-2">
        <button class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">Aanmaken</button>
    </div>
</form>
@endsection
