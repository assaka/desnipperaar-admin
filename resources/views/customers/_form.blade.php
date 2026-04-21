@csrf
@isset($customer) @method('PATCH') @endisset

@if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 mb-4 text-sm">
        @foreach ($errors->all() as $error) <div>{{ $error }}</div> @endforeach
    </div>
@endif

<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-sm font-bold">Naam *</label>
        <input type="text" name="name" required value="{{ old('name', $customer->name ?? '') }}" class="w-full border p-2">
    </div>
    <div>
        <label class="block text-sm font-bold">Bedrijf / organisatie</label>
        <input type="text" name="company" value="{{ old('company', $customer->company ?? '') }}" class="w-full border p-2">
    </div>
    <div>
        <label class="block text-sm font-bold">E-mail *</label>
        <input type="email" name="email" required value="{{ old('email', $customer->email ?? '') }}" class="w-full border p-2">
    </div>
    <div>
        <label class="block text-sm font-bold">Telefoon</label>
        <input type="tel" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" class="w-full border p-2">
    </div>
    <div class="col-span-2">
        <label class="block text-sm font-bold">Adres</label>
        <input type="text" name="address" value="{{ old('address', $customer->address ?? '') }}" class="w-full border p-2">
    </div>
    <div>
        <label class="block text-sm font-bold">Postcode</label>
        <input type="text" name="postcode" value="{{ old('postcode', $customer->postcode ?? '') }}"
               pattern="\d{4}\s?[A-Za-z]{2}"
               placeholder="1034AB"
               class="w-full border p-2 uppercase font-mono">
    </div>
    <div>
        <label class="block text-sm font-bold">Plaats</label>
        <input type="text" name="city" value="{{ old('city', $customer->city ?? 'Amsterdam') }}" class="w-full border p-2">
    </div>
    <div>
        <label class="block text-sm font-bold">Klantreferentie</label>
        <input type="text" name="reference" value="{{ old('reference', $customer->reference ?? '') }}" class="w-full border p-2"
               placeholder="bv. standaard PO-nummer">
    </div>
    <div>
        <label class="block text-sm font-bold">Branche</label>
        <select name="branche" class="w-full border p-2">
            @php $b = old('branche', $customer->branche ?? ''); @endphp
            <option value="">—</option>
            @foreach (['Advocatuur','Notariaat','Accountancy','Zorg','IT / MSP','Financieel','HR','MKB','Particulier'] as $opt)
                <option value="{{ $opt }}" {{ $b === $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-span-2">
        <label class="block text-sm font-bold">Notities</label>
        <textarea name="notes" rows="3" class="w-full border p-2">{{ old('notes', $customer->notes ?? '') }}</textarea>
    </div>
</div>
