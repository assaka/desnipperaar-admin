@extends('layouts.app')
@section('title', 'Nieuwe order')

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black">Nieuwe order</h1>
        <a href="{{ route('orders.index') }}" class="text-sm underline">← terug</a>
    </div>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 mb-4 text-sm">
            @foreach ($errors->all() as $error) <div>{{ $error }}</div> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('orders.store') }}" class="space-y-6">
        @csrf

        <section>
            <h2 class="font-black mb-3">Klant</h2>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold">Naam *</label>
                    <input type="text" name="customer_name" required value="{{ old('customer_name') }}" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">E-mail *</label>
                    <input type="email" name="customer_email" required value="{{ old('customer_email') }}" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Telefoon</label>
                    <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Klantreferentie</label>
                    <input type="text" name="customer_reference" value="{{ old('customer_reference') }}" class="w-full border p-2"
                           placeholder="bv. PO-nummer, ticket-ID">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-bold">Adres</label>
                    <input type="text" name="customer_address" value="{{ old('customer_address') }}" class="w-full border p-2"
                           placeholder="Straatnaam + huisnummer">
                </div>
                <div>
                    <label class="block text-sm font-bold">Postcode</label>
                    <input type="text" name="customer_postcode" value="{{ old('customer_postcode') }}"
                           class="w-full border p-2 uppercase font-mono" placeholder="1034AB">
                    <p class="text-xs text-gray-500 mt-1">1020–1039 → pilotprijs automatisch toegepast</p>
                </div>
                <div>
                    <label class="block text-sm font-bold">Plaats</label>
                    <input type="text" name="customer_city" value="{{ old('customer_city', 'Amsterdam') }}" class="w-full border p-2">
                </div>
            </div>
        </section>

        <section>
            <h2 class="font-black mb-3">Order</h2>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-bold">Leveringsmethode *</label>
                    <select name="delivery_mode" required class="w-full border p-2">
                        <option value="ophaal" {{ old('delivery_mode')=='ophaal' ? 'selected':'' }}>Ophaalservice</option>
                        <option value="breng"  {{ old('delivery_mode')=='breng'  ? 'selected':'' }}>Brengservice</option>
                        <option value="mobiel" {{ old('delivery_mode')=='mobiel' ? 'selected':'' }}>Mobiele vernietiging</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold">Aantal dozen</label>
                    <input type="number" name="box_count" min="0" value="{{ old('box_count', 0) }}" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Aantal rolcontainers</label>
                    <input type="number" name="container_count" min="0" value="{{ old('container_count', 0) }}" class="w-full border p-2">
                </div>
            </div>
            <div class="mt-3">
                <label class="block text-sm font-bold">Notities</label>
                <textarea name="notes" rows="4" class="w-full border p-2">{{ old('notes') }}</textarea>
            </div>
        </section>

        <div class="border-t pt-4 flex gap-3">
            <button class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">Order aanmaken</button>
            <a href="{{ route('orders.index') }}" class="px-4 py-2 border font-bold uppercase">Annuleren</a>
        </div>
    </form>
@endsection
