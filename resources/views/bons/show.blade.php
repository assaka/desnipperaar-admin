@extends('layouts.app')
@section('title', $bon->bon_number)

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <div>
            <h1 class="text-2xl font-black font-mono">{{ $bon->bon_number }}</h1>
            <div class="text-sm text-gray-600">
                {{ ucfirst($bon->mode) }}service ·
                <a href="{{ route('orders.show', $bon->order) }}" class="underline">{{ $bon->order->order_number }}</a>
                @if ($bon->picked_up_at)
                    · <span class="bg-green-700 text-white px-2 py-0.5 text-xs font-bold uppercase">getekend {{ $bon->picked_up_at->format('Y-m-d H:i') }}</span>
                @else
                    · <span class="bg-gray-600 text-white px-2 py-0.5 text-xs font-bold uppercase">nog niet getekend</span>
                @endif
            </div>
        </div>
        <a href="{{ route('orders.show', $bon->order) }}" class="text-sm underline">← order</a>
    </div>

    @if (session('warning'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-3 py-2 mb-4 text-sm">{{ session('warning') }}</div>
    @endif

    <section class="grid grid-cols-2 gap-6 mb-6">
        <div>
            <h2 class="font-black mb-2">Klant</h2>
            <div>{{ $bon->order->customer_name }}</div>
            <div class="text-sm">{{ $bon->order->customer_address }}<br>{{ $bon->order->customer_postcode }} {{ $bon->order->customer_city }}</div>
        </div>
        <div>
            <h2 class="font-black mb-2">Inhoud (verwacht)</h2>
            <div>Dozen: <strong>{{ $bon->order->box_count }}</strong></div>
            <div>Rolcontainers: <strong>{{ $bon->order->container_count }}</strong></div>
            @if ($bon->order->pickup_date)
                <div class="mt-1 text-sm">Gewenst: {{ $bon->order->pickup_date->format('l d F Y') }} ({{ $bon->order->pickup_window ?? 'flexibel' }})</div>
            @endif
        </div>
    </section>

    @php $locked = $bon->picked_up_at && $bon->customer_signature_path; @endphp

    @if ($locked)
        <div class="bg-green-100 border border-green-600 text-green-900 px-3 py-3 mb-4 flex justify-between items-center">
            <div class="text-sm">
                <strong>Bon is bevestigd &amp; getekend op {{ $bon->picked_up_at->format('d-m-Y H:i') }}.</strong>
                Alle velden zijn vergrendeld voor audit-integriteit.
            </div>
            <a href="{{ route('bons.pdf', $bon) }}" target="_blank" class="bg-black text-yellow-400 px-3 py-2 text-xs uppercase font-bold no-underline">Bekijk PDF</a>
        </div>
    @endif

    <form method="POST" action="{{ route('bons.update', $bon) }}" class="space-y-4"
          {{ $locked ? 'onsubmit=return false' : '' }}>
        @csrf @method('PATCH')
        <fieldset {{ $locked ? 'disabled' : '' }} class="space-y-4 {{ $locked ? 'opacity-60 pointer-events-none' : '' }}">

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 text-sm">
                @foreach ($errors->all() as $err) <div>{{ $err }}</div> @endforeach
            </div>
        @endif

        <section>
            <h2 class="font-black mb-3">Chauffeur</h2>
            <select name="driver_id" class="w-full border p-2">
                <option value="">— nog geen chauffeur —</option>
                @foreach ($drivers as $driver)
                    <option value="{{ $driver->id }}" {{ $bon->driver_id == $driver->id ? 'selected' : '' }}>
                        {{ $driver->name }} (****{{ $driver->license_last4 }})
                    </option>
                @endforeach
            </select>
        </section>

        <section x-data="{
            expBoxes: {{ $bon->order->box_count }},
            expCont:  {{ $bon->order->container_count }},
            actBoxes: {{ old('actual_boxes', $bon->actual_boxes ?? $bon->order->box_count) ?: 0 }},
            actCont:  {{ old('actual_containers', $bon->actual_containers ?? $bon->order->container_count) ?: 0 }},
            get diff() { return this.actBoxes !== this.expBoxes || this.actCont !== this.expCont; }
        }">
            <h2 class="font-black mb-3">Werkelijk opgehaald</h2>
            <div x-show="diff" x-cloak class="bg-orange-100 border-l-4 border-orange-500 text-orange-900 px-3 py-2 mb-3 font-bold text-sm flex items-center gap-2">
                <span style="font-size:18px;">⚠</span>
                <span>Werkelijk opgehaald wijkt af van bestelling — deze aantallen staan op de factuur.</span>
            </div>
            <p class="text-xs text-gray-500 mb-2">Pas aan als de klant meer of minder aanbood dan besteld. Besteld is voorgevuld.</p>
            @php
                $mediaCatalog = ['hdd'=>['label'=>'HDD','price'=>9], 'ssd'=>['label'=>'SSD / NVMe','price'=>15], 'usb'=>['label'=>'USB / SD','price'=>6], 'phone'=>['label'=>'Telefoon / tablet','price'=>12], 'laptop'=>['label'=>'Laptop','price'=>19]];
                $actualMedia = $bon->actual_media ?? $bon->order->media_items ?? [];
            @endphp
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold">Dozen <span class="text-xs font-normal text-gray-500">(besteld: {{ $bon->order->box_count }})</span></label>
                    <input type="number" min="0" name="actual_boxes" x-model.number="actBoxes"
                           :class="actBoxes !== expBoxes ? 'border-orange-500 border-2 bg-orange-50' : ''"
                           class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Rolcontainers <span class="text-xs font-normal text-gray-500">(besteld: {{ $bon->order->container_count }})</span></label>
                    <input type="number" min="0" name="actual_containers" x-model.number="actCont"
                           :class="actCont !== expCont ? 'border-orange-500 border-2 bg-orange-50' : ''"
                           class="w-full border p-2">
                </div>
            </div>
            <div class="grid grid-cols-5 gap-2 mt-3">
                @foreach ($mediaCatalog as $key => $item)
                    <div>
                        <label class="block text-xs font-bold">{{ $item['label'] }} <span class="text-gray-500">({{ $bon->order->media_items[$key] ?? 0 }})</span></label>
                        <input type="number" min="0" name="actual_media[{{ $key }}]"
                               value="{{ old('actual_media.'.$key, $actualMedia[$key] ?? 0) }}" class="w-full border p-1 text-sm">
                    </div>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="font-black mb-3">Aanlevering</h2>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold">Datum + tijd opgehaald</label>
                    <input type="datetime-local" name="picked_up_at"
                           value="{{ old('picked_up_at', $bon->picked_up_at?->format('Y-m-d\\TH:i')) }}"
                           class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">Gewicht (kg)</label>
                    <input type="number" step="0.1" name="weight_kg" value="{{ old('weight_kg', $bon->weight_kg) }}" class="w-full border p-2">
                </div>
            </div>
        </section>

        <section>
            <h2 class="font-black mb-3">Zegelnummers</h2>
            <p class="text-xs text-gray-500 mb-2">Eén per regel of gescheiden door komma's.</p>
            <textarea name="seals" rows="4" class="w-full border p-2 font-mono"
                      placeholder="SEAL-000123&#10;SEAL-000124">{{ old('seals', $bon->seals->pluck('seal_number')->implode("\n")) }}</textarea>
        </section>

        <section>
            <h2 class="font-black mb-3">Notities</h2>
            <textarea name="notes" rows="3" class="w-full border p-2">{{ old('notes', $bon->notes) }}</textarea>
        </section>

        <section>
            <h2 class="font-black mb-3">Handtekeningen</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-2">Klant — {{ $bon->order->customer_name }}</label>
                    @if ($bon->customer_signature_path)
                        <div class="border border-green-600 bg-green-50 p-2 text-center">
                            <img src="{{ route('bons.signature', ['bon' => $bon, 'role' => 'customer']) }}" alt="klant-handtekening" style="max-height:120px;margin:0 auto;display:block;">
                            <div class="text-xs text-green-800 mt-1 font-bold uppercase">✓ Getekend</div>
                        </div>
                        <button type="button" class="text-xs underline mt-1" onclick="document.getElementById('sig-cust-wrap').style.display='block';this.style.display='none';">Opnieuw tekenen</button>
                    @endif
                    <div id="sig-cust-wrap" style="{{ $bon->customer_signature_path ? 'display:none;' : '' }}">
                        <canvas id="sig-customer" class="border border-black bg-white" width="400" height="140" style="touch-action:none;width:100%;max-width:400px;"></canvas>
                        <div class="flex gap-2 mt-1">
                            <button type="button" class="text-xs underline" onclick="sigCustomer.clear()">Wissen</button>
                        </div>
                    </div>
                    <input type="hidden" name="customer_signature" id="sig-customer-data">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">Chauffeur — {{ $bon->driver_name_snapshot ?? 'nog niet toegewezen' }}</label>
                    @if ($bon->driver_signature_path)
                        <div class="border border-green-600 bg-green-50 p-2 text-center">
                            <img src="{{ route('bons.signature', ['bon' => $bon, 'role' => 'driver']) }}" alt="chauffeur-handtekening" style="max-height:120px;margin:0 auto;display:block;">
                            <div class="text-xs text-green-800 mt-1 font-bold uppercase">✓ Getekend</div>
                        </div>
                        <button type="button" class="text-xs underline mt-1" onclick="document.getElementById('sig-driv-wrap').style.display='block';this.style.display='none';">Opnieuw tekenen</button>
                    @endif
                    <div id="sig-driv-wrap" style="{{ $bon->driver_signature_path ? 'display:none;' : '' }}">
                        <canvas id="sig-driver" class="border border-black bg-white" width="400" height="140" style="touch-action:none;width:100%;max-width:400px;"></canvas>
                        <div class="flex gap-2 mt-1">
                            <button type="button" class="text-xs underline" onclick="sigDriver.clear()">Wissen</button>
                        </div>
                    </div>
                    <input type="hidden" name="driver_signature" id="sig-driver-data">
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Zodra de klant tekent wordt de ophaaldatum automatisch vastgelegd en gaat de getekende bon als PDF naar de klant. De chauffeur-handtekening wordt automatisch ingevuld vanuit het chauffeur-profiel.</p>
        </section>

        <div class="border-t pt-4 flex gap-3">
            @if (!$locked)
                <button type="submit" class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">Bevestig &amp; mailen</button>
            @endif
            @if ($bon->picked_up_at)
                <a href="{{ route('bons.pdf', $bon) }}" target="_blank" class="px-4 py-2 border font-bold uppercase underline">Print PDF</a>
            @endif
        </div>
        </fieldset>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4/dist/signature_pad.umd.min.js"></script>
    <script>
    (function () {
        var cCanvas = document.getElementById('sig-customer');
        var dCanvas = document.getElementById('sig-driver');
        if (!cCanvas || !dCanvas) return;
        window.sigCustomer = new SignaturePad(cCanvas, { penColor: '#0A0A0A', backgroundColor: '#FFFFFF' });
        window.sigDriver   = new SignaturePad(dCanvas, { penColor: '#0A0A0A', backgroundColor: '#FFFFFF' });

        var form = cCanvas.closest('form');
        form.addEventListener('submit', function () {
            if (!sigCustomer.isEmpty()) document.getElementById('sig-customer-data').value = sigCustomer.toDataURL('image/png');
            if (!sigDriver.isEmpty())   document.getElementById('sig-driver-data').value   = sigDriver.toDataURL('image/png');
        });
    })();
    </script>
@endsection
