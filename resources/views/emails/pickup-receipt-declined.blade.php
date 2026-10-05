@component('emails._layout', ['title' => 'Ophaalmoment komt niet uit '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Ophaalmoment komt de klant niet uit.</h1>

<p>Klant <strong>{{ $order->customer_name }}</strong>@if ($order->customer?->company) ({{ $order->customer->company }})@endif
gaf in de ophaalmail aan dat het moment voor opdracht
<strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>
niet uitkomt. Het moment staat nog op de order.</p>

<div style="background:#F7F7F4;padding:14px 16px;border-left:3px solid #F5C518;margin:20px 0;">
    <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.1em;text-transform:uppercase;color:#555;margin-bottom:6px;">Gepland moment</div>
    <div style="font-weight:900;font-size:14pt;line-height:1.2;">
        {{ $order->pickup_date?->locale('nl')->translatedFormat('l d F Y') ?? '—' }}
    </div>
    <div style="font-size:13px;margin-top:2px;">
        @if (preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}$/', (string) $order->pickup_window))
            {{ str_replace('-', ' – ', $order->pickup_window) }}
        @else
            {{ ucfirst($order->pickup_window ?? 'flexibel') }}
        @endif
    </div>
</div>

<h2 style="font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:20px 0 6px;color:#555;">Wat de klant schreef</h2>
<div style="font-size:14px;line-height:1.6;">
    @if ($order->pickup_receipt_note)
        {!! nl2br(e($order->pickup_receipt_note)) !!}
    @else
        <span style="color:#555;">Geen toelichting.</span>
    @endif
</div>

<h2 style="font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:20px 0 6px;color:#555;">Contact</h2>
<div style="font-size:14px;line-height:1.7;">
    <a href="mailto:{{ $order->customer_email }}" style="color:#0A0A0A;">{{ $order->customer_email }}</a>
    @if ($order->customer_phone)
        &middot; <a href="tel:{{ preg_replace('/\s+/', '', $order->customer_phone) }}" style="color:#0A0A0A;">{{ $order->customer_phone }}</a>
    @endif
</div>

<p style="margin:28px 0;">
    <a href="{{ route('orders.show', $order) }}"
       style="display:inline-block;background:#0A0A0A;color:#F5C518;padding:14px 28px;font-weight:900;font-size:15px;text-transform:uppercase;letter-spacing:0.05em;text-decoration:none;">
        Open de order →
    </a>
</p>
@endcomponent
