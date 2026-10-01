{{-- Gedeelde velden voor create + edit --}}
<div class="col-span-2">
    <label class="block text-sm font-bold mb-1">Naam <span class="text-red-600">*</span></label>
    <input type="text" name="label" required maxlength="120"
           value="{{ old('label', $run->label) }}"
           class="w-full border p-2" placeholder="Bv. Rit Maastricht">
</div>

<div>
    <label class="block text-sm font-bold mb-1">Postcode bestemming <span class="text-red-600">*</span></label>
    <input type="text" name="destination_postcode" required maxlength="10"
           value="{{ old('destination_postcode', $run->destination_postcode) }}"
           class="w-full border p-2 font-mono uppercase" placeholder="1234AB">
    <p class="text-xs text-gray-500 mt-1">Het verste punt van de rit.</p>
</div>

<div>
    <label class="block text-sm font-bold mb-1">Datum</label>
    <input type="date" name="run_date"
           value="{{ old('run_date', $run->run_date?->format('Y-m-d')) }}"
           class="w-full border p-2">
    <p class="text-xs text-gray-500 mt-1">Leeg = nog niet bekend; de klant leest dan "datum volgt".</p>
</div>

<div>
    <label class="block text-sm font-bold mb-1">Max. omweg (km) <span class="text-red-600">*</span></label>
    <input type="number" name="max_detour_km" required min="1" max="200" step="0.5"
           value="{{ old('max_detour_km', $run->max_detour_km) }}"
           class="w-full border p-2 font-mono">
    <p class="text-xs text-gray-500 mt-1">Extra kilometers om de stop in de rit op te nemen, heen en terug samen.</p>
</div>

<div>
    <label class="block text-sm font-bold mb-1">Ankerorder</label>
    <input type="text" name="anchor_order_number" maxlength="40"
           value="{{ old('anchor_order_number', $run->anchorOrder?->order_number) }}"
           class="w-full border p-2 font-mono" placeholder="DS-2026-0123">
    <p class="text-xs text-gray-500 mt-1">De order waarvoor de rit gereden wordt, ter informatie.</p>
</div>

<div class="col-span-2">
    <label class="block text-sm font-bold mb-1">Notities</label>
    <textarea name="notes" rows="2" maxlength="2000" class="w-full border p-2">{{ old('notes', $run->notes) }}</textarea>
</div>

<div class="col-span-2">
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_open" value="1"
               {{ old('is_open', $run->is_open) ? 'checked' : '' }}>
        <span class="font-bold">Open</span>
        <span class="text-gray-500">(dicht = /order biedt hem niet meer aan)</span>
    </label>
</div>
