@extends('public._layout')
@section('title', 'Ophaalmoment wijzigen — '.$order->order_number)

@section('content')
    <h1>Ophaalmoment wijzigen</h1>
    <p>Opdracht <span class="num">{{ $order->order_number }}</span></p>

    @if (!$canReschedule)
        <div class="banner bad">
            Online herplannen is niet meer mogelijk (de ophaling is vandaag of al uitgevoerd). Bel ons op
            <a href="tel:+31610229965" style="color:inherit;"><strong>06-10229965</strong></a> zodat we samen een ander moment kunnen vinden.
        </div>

        @if ($order->pickup_date)
            <div class="meta">
                <div class="row"><span class="k">Huidige afspraak</span>
                    <span class="v">{{ $order->pickup_date->locale('nl')->translatedFormat('l d F Y') }} — {{ ucfirst($order->pickup_window ?? 'flexibel') }}</span></div>
            </div>
        @endif
    @else
        <div class="meta">
            <div class="row"><span class="k">Huidige afspraak</span>
                <span class="v">{{ $order->pickup_date->locale('nl')->translatedFormat('l d F Y') }} — {{ ucfirst($order->pickup_window ?? 'flexibel') }}</span></div>
            <div class="row"><span class="k">Adres</span>
                <span class="v">{{ $order->customer_postcode }} {{ $order->customer_city }}</span></div>
        </div>

        @if ($errors->any())
            <div class="banner bad">
                @foreach ($errors->all() as $err) {{ $err }}<br>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('reschedule.store', $order->public_token) }}">
            @csrf
            <h2>Nieuw voorstel</h2>
            <p>
                <label class="small">Datum (morgen of later)</label><br>
                <input type="date" name="new_date" required
                       min="{{ now()->addDay()->toDateString() }}"
                       value="{{ old('new_date') }}"
                       style="width:100%;padding:10px;border:1px solid #ccc;font-size:15px;">
            </p>
            <p>
                <label class="small">Dagdeel</label><br>
                <select name="new_window" required style="width:100%;padding:10px;border:1px solid #ccc;font-size:15px;">
                    <option value="flexibel" @selected(old('new_window', $order->pickup_window) === 'flexibel')>Flexibel (wij bellen 30 min voor aankomst)</option>
                    <option value="ochtend"  @selected(old('new_window') === 'ochtend')>Ochtend (08:00 – 12:00)</option>
                    <option value="middag"   @selected(old('new_window') === 'middag')>Middag (12:00 – 17:00)</option>
                    <option value="avond"    @selected(old('new_window') === 'avond')>Avond (17:00 – 20:00)</option>
                </select>
            </p>
            <p>
                <label class="small">Toelichting (optioneel)</label><br>
                <textarea name="notes" rows="3" maxlength="2000"
                          style="width:100%;padding:10px;border:1px solid #ccc;font-size:15px;font-family:inherit;">{{ old('notes') }}</textarea>
            </p>
            <button type="submit" class="accept-btn">Verzoek tot wijziging versturen</button>
        </form>
        <p class="small" style="margin-top:12px;">We controleren de beschikbaarheid en bevestigen uw nieuwe ophaalmoment per e-mail — meestal binnen één werkdag.</p>
    @endif
@endsection
