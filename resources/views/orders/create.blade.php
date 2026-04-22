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

    <form method="POST" action="{{ route('orders.store') }}" class="space-y-6"
          x-data="orderForm(@js([
              'preselected' => $preselected ?? null,
              'searchUrl' => route('customers.search'),
              'quoteUrl' => route('pricing.quote'),
          ]))">
        @csrf

        {{-- ── KLANT ── --}}
        <section>
            <div class="flex justify-between items-baseline mb-3">
                <h2 class="font-black">Klant</h2>
                <label class="text-sm"><input type="radio" x-model="mode" value="existing"> Bestaande klant</label>
                <label class="text-sm"><input type="radio" x-model="mode" value="new"> Nieuwe klant</label>
            </div>

            {{-- Existing-customer search --}}
            <div x-show="mode === 'existing'" x-cloak class="relative mb-3">
                <input type="text" x-model="query" @input.debounce.250ms="search" @focus="search"
                       placeholder="Zoek klant op naam, bedrijf of e-mail…"
                       class="w-full border p-2">
                <input type="hidden" name="customer_id" :value="selected?.id ?? ''">
                <div x-show="results.length > 0 && !selected" x-cloak
                     class="absolute z-10 bg-white border w-full max-h-64 overflow-auto shadow-lg">
                    <template x-for="c in results" :key="c.id">
                        <button type="button" @click="pick(c)"
                                class="block w-full text-left p-2 hover:bg-yellow-50 border-b last:border-0">
                            <div class="font-bold" x-text="c.name"></div>
                            <div class="text-sm text-gray-600">
                                <span x-text="c.company"></span> · <span x-text="c.email"></span>
                                <span x-show="c.postcode" x-text="'· ' + c.postcode"></span>
                            </div>
                        </button>
                    </template>
                </div>
                <div x-show="selected" class="mt-3 p-3 bg-yellow-50 border-l-4 border-yellow-400" x-cloak>
                    <div class="font-bold" x-text="selected?.name"></div>
                    <div class="text-sm" x-text="(selected?.company ? selected.company + ' · ' : '') + selected?.email"></div>
                    <div class="text-sm font-mono" x-text="selected?.postcode + ' ' + (selected?.city ?? '')"></div>
                    <button type="button" @click="clearSelection" class="text-xs underline mt-1">Andere klant kiezen</button>
                </div>
            </div>

            {{-- New-customer inline form --}}
            <div x-show="mode === 'new'" x-cloak class="grid grid-cols-2 gap-3 p-3 bg-gray-50">
                <div>
                    <label class="block text-sm font-bold">Naam *</label>
                    <input type="text" name="new_customer[name]" x-bind:required="mode === 'new'" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Bedrijf</label>
                    <input type="text" name="new_customer[company]" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">E-mail *</label>
                    <input type="email" name="new_customer[email]" x-bind:required="mode === 'new'" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Telefoon</label>
                    <input type="tel" name="new_customer[phone]" class="w-full border p-2">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-bold">Adres</label>
                    <input type="text" name="new_customer[address]" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Postcode</label>
                    <input type="text" name="new_customer[postcode]" x-model="postcode"
                           pattern="\d{4}\s?[A-Za-z]{2}" placeholder="1034AB"
                           class="w-full border p-2 uppercase font-mono">
                </div>
                <div>
                    <label class="block text-sm font-bold">Plaats</label>
                    <input type="text" name="new_customer[city]" value="Amsterdam" class="w-full border p-2">
                </div>
            </div>
        </section>

        {{-- ── ORDER ── --}}
        <section>
            <h2 class="font-black mb-3">Order</h2>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-bold">Leveringsmethode *</label>
                    <select name="delivery_mode" x-model="mode_delivery" required class="w-full border p-2">
                        <option value="ophaal">Ophaalservice</option>
                        <option value="breng">Brengservice</option>
                        <option value="mobiel">Mobiele vernietiging</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold">Aantal dozen</label>
                    <input type="number" name="box_count" x-model.number="boxes" min="0" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Rolcontainers 240 L</label>
                    <input type="number" name="container_count" x-model.number="containers" min="0" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Gewenste ophaaldatum</label>
                    <input type="date" name="pickup_date" x-model="pickupDate"
                           :min="today" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Dagdeel</label>
                    <select name="pickup_window" class="w-full border p-2">
                        <option value="flexibel">Flexibel</option>
                        <option value="ochtend">Ochtend</option>
                        <option value="middag">Middag</option>
                        <option value="avond">Avond</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold">Kennismaking</label>
                    <label class="flex items-center gap-2 mt-2 text-sm">
                        <input type="checkbox" name="first_box_free" value="1" x-model="firstBoxFree">
                        Eerste doos gratis
                    </label>
                </div>
                <div class="col-span-3 text-xs text-gray-500 italic">
                    Chauffeur + ophaaldatum komen in de volgende stap (Plan ophaling) op het order-detail.
                </div>
            </div>
            <div class="mt-3">
                <label class="block text-sm font-bold">Notities</label>
                <textarea name="notes" rows="3" class="w-full border p-2"></textarea>
            </div>
        </section>

        {{-- ── PRIJSOVERZICHT ── --}}
        <section x-show="quote && quote.lines.length > 0" x-cloak class="bg-gray-50 border-l-4 border-yellow-400 p-4">
            <div class="flex justify-between items-baseline mb-3">
                <h2 class="font-black">Prijsoverzicht</h2>
                <span x-show="pilot" class="bg-yellow-400 text-black px-2 py-1 text-xs font-bold uppercase">Noord-pilot · 20% korting</span>
            </div>
            <table class="w-full text-sm">
                <template x-for="line in quote?.lines ?? []" :key="line.label">
                    <tr class="border-b">
                        <td class="py-1" x-text="line.label"></td>
                        <td class="text-right font-mono" x-text="line.qty + ' × € ' + line.unit.toFixed(2).replace('.',',')"></td>
                        <td class="text-right font-bold font-mono" x-text="'€ ' + line.subtotal.toFixed(2).replace('.',',')"></td>
                    </tr>
                </template>
                <tr>
                    <td class="pt-2 text-gray-600">Subtotaal</td>
                    <td></td>
                    <td class="text-right font-mono pt-2" x-text="'€ ' + (quote?.subtotal ?? 0).toFixed(2).replace('.',',')"></td>
                </tr>
                <tr>
                    <td class="text-gray-600">BTW 21%</td>
                    <td></td>
                    <td class="text-right font-mono" x-text="'€ ' + (quote?.vat ?? 0).toFixed(2).replace('.',',')"></td>
                </tr>
                <tr class="border-t-2 border-black">
                    <td class="pt-2 font-bold">Totaal incl. BTW</td>
                    <td></td>
                    <td class="pt-2 text-right font-bold text-lg font-mono" x-text="'€ ' + (quote?.total ?? 0).toFixed(2).replace('.',',')"></td>
                </tr>
            </table>
            <p class="text-xs text-gray-500 mt-2" x-show="pilot">
                Postcode valt binnen 1020–1039. Pilot-prijs automatisch toegepast.
            </p>
        </section>

        <div class="border-t pt-4 flex gap-3">
            <button class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">Order aanmaken</button>
            <a href="{{ route('orders.index') }}" class="px-4 py-2 border font-bold uppercase">Annuleren</a>
        </div>
    </form>

    <script>
        function orderForm(cfg) {
            return {
                mode: cfg.preselected ? 'existing' : 'existing',
                searchUrl: cfg.searchUrl,
                quoteUrl: cfg.quoteUrl,
                query: '',
                results: [],
                selected: cfg.preselected ?? null,
                postcode: '',
                boxes: 0,
                containers: 0,
                firstBoxFree: false,
                pickupDate: '',
                mode_delivery: 'ophaal',
                quote: null,
                today: new Date().toISOString().slice(0,10),

                init() {
                    this.$watch('boxes',        () => this.refreshQuote());
                    this.$watch('containers',   () => this.refreshQuote());
                    this.$watch('firstBoxFree', () => this.refreshQuote());
                    this.$watch('postcode',     () => this.refreshQuote());
                    this.$watch('selected',     () => { this.postcode = this.selected?.postcode ?? this.postcode; this.refreshQuote(); });
                },

                get pilot() {
                    const pc = (this.selected?.postcode || this.postcode || '').replace(/\s/g,'');
                    const n = parseInt(pc.substring(0,4), 10);
                    return n >= 1020 && n <= 1039;
                },

                async search() {
                    if (this.query.length < 2) { this.results = []; return; }
                    const r = await fetch(this.searchUrl + '?q=' + encodeURIComponent(this.query), {headers:{Accept:'application/json'}});
                    this.results = await r.json();
                },

                pick(c) { this.selected = c; this.results = []; this.query = ''; },
                clearSelection() { this.selected = null; this.query = ''; },

                async refreshQuote() {
                    if (!this.boxes && !this.containers) { this.quote = null; return; }
                    const p = new URLSearchParams({
                        boxes: this.boxes || 0,
                        containers: this.containers || 0,
                        pilot: this.pilot ? 1 : 0,
                        first_box_free: this.firstBoxFree ? 1 : 0,
                    });
                    const r = await fetch(this.quoteUrl + '?' + p, {headers:{Accept:'application/json'}});
                    this.quote = await r.json();
                },
            };
        }
    </script>
@endsection
