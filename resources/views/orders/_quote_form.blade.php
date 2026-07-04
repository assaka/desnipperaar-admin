<form method="POST" action="{{ route('orders.send-quote', $order) }}" class="mt-3 space-y-3" id="quote-form">
    @csrf

    {{-- Itemised quote rows. When any row has a description, the total below is
         computed from the rows and the manual amount field is ignored. Rows marked
         "Optioneel" are add-ons the customer can tick on the public quote page; they
         are NOT part of the base amount. --}}
    <div>
        <label class="block text-sm font-bold mb-1">Offerteregels <span class="font-normal text-gray-500">(alleen offerte)</span></label>
        <table class="w-full text-sm" id="quote-lines">
            <thead>
                <tr class="text-left text-gray-500">
                    <th class="pb-1">Omschrijving</th>
                    <th class="pb-1 w-20 text-right">Aantal</th>
                    <th class="pb-1 w-28 text-right">Prijs €</th>
                    <th class="pb-1 w-16 text-center">Optie</th>
                    <th class="pb-1 w-28 text-right">Subtotaal</th>
                    <th class="pb-1 w-8"></th>
                </tr>
            </thead>
            <tbody id="quote-lines-body">
                @php $rows = old('lines', $order->quote_lines ?: [['label' => '', 'qty' => 1, 'unit' => '']]); @endphp
                @foreach ($rows as $i => $r)
                    <tr class="quote-line">
                        <td class="pr-2 py-1"><input type="text" name="lines[{{ $i }}][label]" value="{{ $r['label'] ?? '' }}" class="w-full border p-1" placeholder="bv. Vernietiging 10 archiefdozen"></td>
                        <td class="pr-2 py-1"><input type="number" step="0.01" min="0" name="lines[{{ $i }}][qty]" value="{{ $r['qty'] ?? '' }}" class="w-full border p-1 text-right qty"></td>
                        <td class="pr-2 py-1"><input type="number" step="0.01" min="0" name="lines[{{ $i }}][unit]" value="{{ $r['unit'] ?? '' }}" class="w-full border p-1 text-right unit"></td>
                        <td class="pr-2 py-1 text-center"><input type="checkbox" name="lines[{{ $i }}][optional]" value="1" class="opt" @checked(!empty($r['optional'])) title="Klant kan deze regel zelf kiezen"></td>
                        <td class="pr-2 py-1 text-right font-mono line-sub">€ 0,00</td>
                        <td class="py-1 text-center"><button type="button" class="text-red-600 font-bold remove-line" title="Regel verwijderen">×</button></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t font-bold">
                    <td colspan="4" class="pt-1 text-right">Basisbedrag excl. btw</td>
                    <td class="pt-1 text-right font-mono" id="quote-lines-total">€ 0,00</td>
                    <td></td>
                </tr>
                <tr class="text-gray-500">
                    <td colspan="4" class="text-right">Optionele regels (klant kiest)</td>
                    <td class="text-right font-mono" id="quote-lines-optional">€ 0,00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        <button type="button" id="add-line" class="mt-1 text-sm border px-2 py-1 font-bold">+ regel</button>
        <p class="text-xs text-gray-500 mt-1">Vink <strong>Optie</strong> aan voor een regel die de klant zelf kan aan- of uitzetten op de offertepagina. Het basisbedrag telt alleen de niet-optionele regels.</p>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-bold">Bedrag excl. btw (€) <span class="font-normal text-gray-500">(zonder regels)</span></label>
            <input type="number" step="0.01" min="0" name="quoted_amount_excl_btw"
                   value="{{ old('quoted_amount_excl_btw', $order->quote_lines ? null : $order->quoted_amount_excl_btw) }}"
                   class="w-full border p-2 font-mono" id="manual-amount">
            <p class="text-xs text-gray-500 mt-1">Gebruik dit alleen als je geen regels invult. Laat leeg bij een bericht.</p>
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

<script>
(function () {
    var body  = document.getElementById('quote-lines-body');
    var addBtn = document.getElementById('add-line');
    if (!body) return;

    function euro(n) { return '€ ' + (n || 0).toLocaleString('nl-NL', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }

    function recalc() {
        var base = 0, optional = 0;
        body.querySelectorAll('tr.quote-line').forEach(function (row) {
            var qty  = parseFloat(row.querySelector('.qty').value)  || 0;
            var unit = parseFloat(row.querySelector('.unit').value) || 0;
            var sub  = qty * unit;
            if (row.querySelector('.opt').checked) { optional += sub; } else { base += sub; }
            row.querySelector('.line-sub').textContent = euro(sub);
        });
        document.getElementById('quote-lines-total').textContent = euro(base);
        document.getElementById('quote-lines-optional').textContent = euro(optional);
    }

    function reindex() {
        body.querySelectorAll('tr.quote-line').forEach(function (row, i) {
            row.querySelectorAll('input').forEach(function (inp) {
                inp.name = inp.name.replace(/lines\[\d+\]/, 'lines[' + i + ']');
            });
        });
    }

    function addRow() {
        var first = body.querySelector('tr.quote-line');
        var clone = first.cloneNode(true);
        clone.querySelectorAll('input').forEach(function (inp) {
            if (inp.type === 'checkbox') { inp.checked = false; }
            else if (inp.classList.contains('qty')) { inp.value = 1; }
            else { inp.value = ''; }
        });
        clone.querySelector('.line-sub').textContent = euro(0);
        body.appendChild(clone);
        reindex();
    }

    addBtn.addEventListener('click', addRow);

    body.addEventListener('input', function (e) {
        if (e.target.classList.contains('qty') || e.target.classList.contains('unit')) recalc();
    });
    body.addEventListener('change', function (e) {
        if (e.target.classList.contains('opt')) recalc();
    });

    body.addEventListener('click', function (e) {
        if (!e.target.classList.contains('remove-line')) return;
        var rows = body.querySelectorAll('tr.quote-line');
        if (rows.length === 1) {
            var row = rows[0];
            row.querySelectorAll('input').forEach(function (inp) {
                if (inp.type === 'checkbox') inp.checked = false;
                else inp.value = inp.classList.contains('qty') ? 1 : '';
            });
        } else {
            e.target.closest('tr').remove();
        }
        reindex();
        recalc();
    });

    recalc();
})();
</script>
