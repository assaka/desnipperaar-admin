@extends('layouts.app')
@section('title', 'Nieuwe offerte')

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black">Nieuwe offerte</h1>
        <a href="{{ route('offertes.index') }}" class="text-sm underline">← terug</a>
    </div>

    <p class="text-sm text-gray-600 mb-4">
        Voor een aanvraag die per e-mail of telefoon binnenkomt. Leg hier vast wat de klant vraagt.
        Op de volgende pagina stel je de offerteregels samen en verstuur je de offerte.
    </p>

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 mb-4 text-sm">
            @foreach ($errors->all() as $error) <div>{{ $error }}</div> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('offertes.store') }}" class="space-y-6"
          x-data="offerteForm(@js([
              'preselected' => $preselected ?? null,
              'searchUrl' => route('customers.search'),
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
                <input type="hidden" name="customer_id" :value="mode === 'existing' ? (selected?.id ?? '') : ''">
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
                    <div class="text-sm font-mono" x-text="(selected?.postcode ?? '') + ' ' + (selected?.city ?? '')"></div>
                    <button type="button" @click="clearSelection" class="text-xs underline mt-1">Andere klant kiezen</button>
                </div>
            </div>

            {{-- New-customer inline form --}}
            <div x-show="mode === 'new'" x-cloak class="grid grid-cols-2 gap-3 p-3 bg-gray-50">
                <div>
                    <label class="block text-sm font-bold">Naam *</label>
                    <input type="text" name="new_customer[name]" value="{{ old('new_customer.name') }}"
                           x-bind:required="mode === 'new'" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Bedrijf</label>
                    <input type="text" name="new_customer[company]" value="{{ old('new_customer.company') }}" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">E-mail *</label>
                    <input type="email" name="new_customer[email]" value="{{ old('new_customer.email') }}"
                           x-bind:required="mode === 'new'" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Telefoon</label>
                    <input type="tel" name="new_customer[phone]" value="{{ old('new_customer.phone') }}" class="w-full border p-2">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-bold">Adres</label>
                    <input type="text" name="new_customer[address]" value="{{ old('new_customer.address') }}" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Postcode</label>
                    <input type="text" name="new_customer[postcode]" value="{{ old('new_customer.postcode') }}"
                           pattern="\d{4}\s?[A-Za-z]{2}" placeholder="1034AB"
                           class="w-full border p-2 uppercase font-mono">
                </div>
                <div>
                    <label class="block text-sm font-bold">Plaats</label>
                    <input type="text" name="new_customer[city]" value="{{ old('new_customer.city') }}" class="w-full border p-2">
                </div>
            </div>
        </section>

        {{-- ── AANVRAAG ── --}}
        <section>
            <h2 class="font-black mb-3">Aanvraag</h2>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-bold">Leveringsmethode *</label>
                    <select name="delivery_mode" required class="w-full border p-2">
                        @foreach (['ophaal' => 'Ophaalservice', 'breng' => 'Brengservice', 'mobiel' => 'Mobiele vernietiging'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('delivery_mode') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold">Taal offerte *</label>
                    <select name="locale" x-model="locale" required class="w-full border p-2">
                        <option value="nl">Nederlands</option>
                        <option value="en">Engels</option>
                        <option value="fr">Frans</option>
                        <option value="es">Spaans</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold">Branche</label>
                    <input type="text" name="branche" value="{{ old('branche') }}" class="w-full border p-2"
                           placeholder="bv. zorg, advocatuur">
                </div>
                <div>
                    <label class="block text-sm font-bold">Materiaal</label>
                    <input type="text" name="materiaal" value="{{ old('materiaal') }}" class="w-full border p-2"
                           placeholder="bv. papier, harde schijven">
                </div>
                <div>
                    <label class="block text-sm font-bold">Volume</label>
                    <input type="text" name="volume" value="{{ old('volume') }}" class="w-full border p-2"
                           placeholder="bv. 40 dozen, 3 pallets">
                </div>
                <div>
                    <label class="block text-sm font-bold">Termijn</label>
                    <input type="text" name="termijn" value="{{ old('termijn') }}" class="w-full border p-2"
                           placeholder="bv. binnen 2 weken">
                </div>
                <div>
                    <label class="block text-sm font-bold">Aantal dozen</label>
                    <input type="number" name="box_count" value="{{ old('box_count') }}" min="0" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Rolcontainers 240 L</label>
                    <input type="number" name="container_count" value="{{ old('container_count') }}" min="0" class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Gevonden via</label>
                    <input type="text" name="gevonden_via" value="{{ old('gevonden_via') }}" class="w-full border p-2"
                           placeholder="bv. Google, doorverwijzing">
                </div>
                <div class="col-span-3 text-xs text-gray-500 italic">
                    Dozen en containers zijn alleen een richtprijs op de detailpagina. Het bedrag op de offerte
                    bepaal je daar zelf met offerteregels.
                </div>
            </div>
            <div class="mt-3">
                <label class="block text-sm font-bold">De aanvraag zelf</label>
                <textarea name="bericht" rows="6" class="w-full border p-2"
                          placeholder="Plak hier de mail of noteer wat de klant aan de telefoon vroeg.">{{ old('bericht') }}</textarea>
            </div>
            <label class="flex items-center gap-2 mt-3 text-sm">
                <input type="checkbox" name="notify" value="1" @checked(old('notify'))>
                Stuur de klant een ontvangstbevestiging
            </label>
            <p class="text-xs text-gray-500 mt-1">
                Alleen aanvinken als de klant nog niets van ons heeft gehoord. De offerte zelf verstuur je op de volgende pagina.
            </p>
        </section>

        <div class="border-t pt-4 flex gap-3">
            <button class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">Offerte aanmaken</button>
            <a href="{{ route('offertes.index') }}" class="px-4 py-2 border font-bold uppercase">Annuleren</a>
        </div>
    </form>

    <script>
        function offerteForm(cfg) {
            return {
                mode: 'existing',
                searchUrl: cfg.searchUrl,
                query: '',
                results: [],
                selected: cfg.preselected ?? null,
                locale: cfg.preselected?.locale ?? '{{ old('locale', 'nl') }}',

                async search() {
                    if (this.query.length < 2) { this.results = []; return; }
                    const r = await fetch(this.searchUrl + '?q=' + encodeURIComponent(this.query), {headers:{Accept:'application/json'}});
                    this.results = await r.json();
                },

                // De taal van de offerte volgt de klant, tenzij je hem daarna zelf omzet.
                pick(c) {
                    this.selected = c;
                    this.results = [];
                    this.query = '';
                    if (c.locale) this.locale = c.locale;
                },
                clearSelection() { this.selected = null; this.query = ''; },
            };
        }
    </script>
@endsection
