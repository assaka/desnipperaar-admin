@extends('layouts.app')
@section('title', 'Login')

@section('content')
    <h1 class="text-2xl font-black mb-4">Admin login</h1>
    <form method="POST" action="{{ route('login') }}" class="space-y-4 max-w-sm">
        @csrf
        <div>
            <label class="block text-sm font-bold">E-mail</label>
            <input type="email" name="email" required autofocus class="w-full border p-2" value="{{ old('email') }}">
            @error('email') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="block text-sm font-bold">Wachtwoord</label>
            <input type="password" name="password" required class="w-full border p-2">
            @error('password') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>
        <button class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">Inloggen</button>
    </form>
@endsection
