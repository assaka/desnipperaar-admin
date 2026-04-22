<form method="POST" action="{{ route('orders.send-quote', $order) }}" class="mt-3 space-y-3">
    @csrf
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-bold">Bedrag excl. btw (€) *</label>
            <input type="number" step="0.01" min="0" name="quoted_amount_excl_btw" required
                   value="{{ old('quoted_amount_excl_btw', $order->quoted_amount_excl_btw) }}"
                   class="w-full border p-2 font-mono">
        </div>
        <div>
            <label class="block text-sm font-bold">Geldig t/m</label>
            <input type="date" name="quote_valid_until"
                   value="{{ old('quote_valid_until', $order->quote_valid_until?->format('Y-m-d') ?? now()->addDays(30)->format('Y-m-d')) }}"
                   class="w-full border p-2">
        </div>
    </div>
    <div>
        <label class="block text-sm font-bold">Scope / voorwaarden *</label>
        <textarea name="quote_body" rows="6" required
                  class="w-full border p-2"
                  placeholder="Wat is inbegrepen, aantal, datum, eventuele NDA-verplichtingen...">{{ old('quote_body', $order->quote_body) }}</textarea>
    </div>
    <button class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">
        {{ $order->quote_sent_at ? 'Offerte bijwerken & opnieuw versturen' : 'Verstuur offerte naar klant' }}
    </button>
</form>
