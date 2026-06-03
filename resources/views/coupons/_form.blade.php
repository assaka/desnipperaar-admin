{{-- Shared form fields for create + edit --}}
<div class="col-span-2">
    <label class="block text-sm font-bold mb-1">Code <span class="text-red-600">*</span></label>
    <input type="text" name="code" required maxlength="50"
           value="{{ old('code', $coupon->code ?? '') }}"
           class="w-full border p-2 font-mono uppercase tracking-widest"
           style="text-transform:uppercase">
    <p class="text-xs text-gray-500 mt-1">Wordt automatisch hoofdletters.</p>
</div>

<div>
    <label class="block text-sm font-bold mb-1">Type <span class="text-red-600">*</span></label>
    <select name="type" required class="w-full border p-2">
        <option value="percentage" {{ old('type', $coupon->type ?? '') === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
        <option value="fixed"      {{ old('type', $coupon->type ?? '') === 'fixed'      ? 'selected' : '' }}>Vast bedrag (€)</option>
    </select>
</div>

<div>
    <label class="block text-sm font-bold mb-1">Waarde <span class="text-red-600">*</span></label>
    <input type="number" name="value" required min="0" step="0.01"
           value="{{ old('value', $coupon->value ?? '') }}"
           class="w-full border p-2 font-mono"
           placeholder="Bv. 10 (%) of 5.00 (€)">
</div>

<div>
    <label class="block text-sm font-bold mb-1">Min. orderbedrag (€ excl. BTW)</label>
    <input type="number" name="min_order_amount" min="0" step="0.01"
           value="{{ old('min_order_amount', $coupon->min_order_amount ?? '') }}"
           class="w-full border p-2 font-mono"
           placeholder="Leeg = geen minimum">
</div>

<div>
    <label class="block text-sm font-bold mb-1">Max. gebruik</label>
    <input type="number" name="max_uses" min="1" step="1"
           value="{{ old('max_uses', $coupon->max_uses ?? '') }}"
           class="w-full border p-2 font-mono"
           placeholder="Leeg = onbeperkt">
</div>

<div>
    <label class="block text-sm font-bold mb-1">Verloopt op</label>
    <input type="datetime-local" name="expires_at"
           value="{{ old('expires_at', isset($coupon->expires_at) ? $coupon->expires_at?->format('Y-m-d\TH:i') : '') }}"
           class="w-full border p-2">
    <p class="text-xs text-gray-500 mt-1">Leeg = nooit verlopen.</p>
</div>

<div class="col-span-2">
    <label class="block text-sm font-bold mb-1">Interne omschrijving</label>
    <input type="text" name="description" maxlength="255"
           value="{{ old('description', $coupon->description ?? '') }}"
           class="w-full border p-2"
           placeholder="Bv. LinkedIn campagne juni 2026">
</div>

<div class="col-span-2">
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1"
               {{ old('is_active', $coupon->is_active ?? true) ? 'checked' : '' }}>
        <span class="font-bold">Actief</span>
    </label>
</div>
