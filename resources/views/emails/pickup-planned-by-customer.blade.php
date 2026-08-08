@php $isVerzet = !empty($previous ?? null); @endphp
@component('emails._layout', ['title' => ($isVerzet ? 'Ophaling verzet ' : 'Ophaling gepland ').$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Klant {{ $isVerzet ? 'verzette' : 'plande' }} zijn ophaling zelf.</h1>

<p>Klant <strong>{{ $order->customer_name }}</strong>@if ($order->customer?->company) ({{ $order->customer->company }})@endif
koos online een {{ $isVerzet ? 'nieuw' : '' }} ophaalmoment voor opdracht
<strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
Het moment kwam uit onze eigen lijst met beschikbare momenten, dus er is ruimte in dat uur.</p>

<div style="background:#F7F7F4;padding:14px 16px;border-left:3px solid #F5C518;margin:20px 0;">
    <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.1em;text-transform:uppercase;color:#555;margin-bottom:6px;">Gekozen moment</div>
    <div style="font-weight:900;font-size:14pt;line-height:1.2;">
        {{ $order->pickup_date?->locale('nl')->translatedFormat('l d F Y') ?? '—' }}
    </div>
    <div style="font-size:13px;margin-top:2px;">
        @if (preg_match('/^\d{2}:00-\d{2}:00$/', (string) $order->pickup_window))
            {{ str_replace('-', ' – ', $order->pickup_window) }}
        @else
            {{ ucfirst($order->pickup_window ?? 'flexibel') }}
        @endif
    </div>
    @if ($isVerzet)
        <div style="font-size:12px;color:#555;margin-top:8px;padding-top:8px;border-top:1px dashed #CCC;">
            Stond eerder op <strong>{{ $previous }}</strong>. Die ruimte is nu weer vrij.
        </div>
    @endif
</div>

<h2 style="font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:20px 0 6px;color:#555;">Adres</h2>
<div style="font-size:14px;line-height:1.7;">
    {{ $order->customer_address }}<br>
    {{ $order->customer_postcode }} {{ $order->customer_city }}
</div>

<h2 style="font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:20px 0 6px;color:#555;">Contact</h2>
<div style="font-size:14px;line-height:1.7;">
    <a href="mailto:{{ $order->customer_email }}" style="color:#0A0A0A;">{{ $order->customer_email }}</a>
    @if ($order->customer_phone)
        &middot; <a href="tel:{{ preg_replace('/\s+/', '', $order->customer_phone) }}" style="color:#0A0A0A;">{{ $order->customer_phone }}</a>
    @endif
</div>

@php $chauffeur = $driverName ?? null; @endphp

<p style="margin:28px 0;">
    <a href="{{ route('orders.show', $order) }}"
       style="display:inline-block;background:#0A0A0A;color:#F5C518;padding:14px 28px;font-weight:900;font-size:15px;text-transform:uppercase;letter-spacing:0.05em;text-decoration:none;">
        {{ $chauffeur ? 'Open de order →' : 'Open order & wijs chauffeur toe →' }}
    </a>
</p>

@if ($chauffeur)
    <p style="font-size:12px;color:#555;">De rit staat bevestigd op naam van <strong>{{ $chauffeur }}</strong> en de bon is aangemaakt. Er hoeft niets meer te gebeuren; klopt er iets niet, dan pas je het op de order aan.</p>
@else
    <p style="font-size:12px;color:#555;">De datum staat al op de order. Wat er nog moet gebeuren is een chauffeur kiezen, zodat de bon wordt aangemaakt.</p>
@endif
@endcomponent
