<form method="POST" action="{{ route('orders.send-quote', $order) }}" class="mt-3 space-y-3">
    @csrf
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-bold">Bedrag excl. btw (€)</label>
            <input type="number" step="0.01" min="0" name="quoted_amount_excl_btw"
                   value="{{ old('quoted_amount_excl_btw', $order->quoted_amount_excl_btw) }}"
                   class="w-full border p-2 font-mono">
            <p class="text-xs text-gray-500 mt-1">Verplicht voor een offerte. Laat leeg bij een bericht (aanvullende gegevens of een vraag).</p>
        </div>
        <div>
            <label class="block text-sm font-bold">Geldig t/m <span class="font-normal text-gray-500">(alleen offerte)</span></label>
            <input type="date" name="quote_valid_until"
                   value="{{ old('quote_valid_until', $order->quote_valid_until?->format('Y-m-d') ?? now()->addDays(30)->format('Y-m-d')) }}"
                   class="w-full border p-2">
        </div>
    </div>
    <div>
        <label class="block text-sm font-bold">Bericht / scope / voorwaarden *</label>
        <textarea name="quote_body" rows="6" required
                  class="w-full border p-2"
                  placeholder="Bij een offerte: wat is inbegrepen, aantal, datum, eventuele NDA-verplichtingen. Bij een bericht: je vraag of aanvullende informatie.">{{ old('quote_body', $order->quote_body) }}</textarea>
    </div>
    <div class="flex flex-wrap gap-2">
        <button type="submit" name="intent" value="message"
                class="bg-white border-2 border-black text-black px-4 py-2 font-bold uppercase">
            Verstuur bericht
        </button>
        <button type="submit" name="intent" value="offer"
                class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">
            {{ $order->quoted_amount_excl_btw !== null ? 'Offerte bijwerken & opnieuw versturen' : 'Verstuur offerte' }}
        </button>
    </div>
    <p class="text-xs text-gray-500">
        <strong>Bericht</strong> = alleen informatie of een vraag. Geen bedrag en geen accepteerknop.
        <strong>Offerte</strong> = bindend voorstel met bedrag en accepteerknop voor de klant.
    </p>
</form>
