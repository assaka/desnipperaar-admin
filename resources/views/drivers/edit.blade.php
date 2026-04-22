@extends('layouts.app')
@section('title', $driver->name)

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <h1 class="text-2xl font-black">{{ $driver->name }}</h1>
        <a href="{{ route('drivers.index') }}" class="text-sm underline">← chauffeurs</a>
    </div>

    <form method="POST" action="{{ route('drivers.update', $driver) }}" class="space-y-4">
        @csrf @method('PATCH')

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 text-sm">
                @foreach ($errors->all() as $err) <div>{{ $err }}</div> @endforeach
            </div>
        @endif

        <div class="grid grid-cols-4 gap-3">
            <div class="col-span-2">
                <label class="block text-sm font-bold">Naam</label>
                <input type="text" name="name" required value="{{ old('name', $driver->name) }}" class="w-full border p-2">
            </div>
            <div>
                <label class="block text-sm font-bold">Rijbewijs (laatste 4)</label>
                <input type="text" name="license_last4" required minlength="4" maxlength="4" pattern="[A-Z0-9]{4}"
                       value="{{ old('license_last4', $driver->license_last4) }}" class="w-full border p-2 font-mono uppercase">
            </div>
            <div>
                <label class="block text-sm font-bold">VOG geldig t/m</label>
                <input type="date" name="vog_valid_until" value="{{ old('vog_valid_until', $driver->vog_valid_until?->format('Y-m-d')) }}" class="w-full border p-2">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="active" value="1" {{ old('active', $driver->active) ? 'checked' : '' }}> Actief
        </label>

        <section>
            <h2 class="font-black mb-3">Handtekening (eenmalig)</h2>
            <p class="text-sm text-gray-600 mb-2">Wordt automatisch gebruikt op elke bon waar deze chauffeur aan gekoppeld wordt.</p>
            @if ($driver->signature_path)
                <div class="border border-green-600 bg-green-50 p-2 text-center inline-block">
                    <img src="{{ route('drivers.signature', $driver) }}?t={{ now()->timestamp }}" style="max-height:120px;display:block;">
                    <div class="text-xs text-green-800 mt-1 font-bold uppercase">✓ Vastgelegd</div>
                </div>
                <button type="button" class="text-xs underline block mt-2" onclick="document.getElementById('sig-wrap').style.display='block';this.style.display='none';">Opnieuw tekenen</button>
            @endif
            <div id="sig-wrap" style="{{ $driver->signature_path ? 'display:none;' : '' }}">
                <canvas id="sig-pad" class="border border-black bg-white" width="500" height="160" style="touch-action:none;width:100%;max-width:500px;"></canvas>
                <div class="flex gap-2 mt-1">
                    <button type="button" class="text-xs underline" onclick="sigPad.clear()">Wissen</button>
                </div>
            </div>
            <input type="hidden" name="signature" id="sig-data">
        </section>

        <div class="border-t pt-4 flex gap-3">
            <button type="submit" class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">Opslaan</button>
            <a href="{{ route('drivers.index') }}" class="px-4 py-2 border font-bold uppercase">Annuleren</a>
        </div>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4/dist/signature_pad.umd.min.js"></script>
    <script>
    (function () {
        var canvas = document.getElementById('sig-pad');
        if (!canvas) return;
        window.sigPad = new SignaturePad(canvas, { penColor: '#0A0A0A', backgroundColor: '#FFFFFF' });
        var form = canvas.closest('form');
        form.addEventListener('submit', function () {
            if (!sigPad.isEmpty()) document.getElementById('sig-data').value = sigPad.toDataURL('image/png');
        });
    })();
    </script>
@endsection
