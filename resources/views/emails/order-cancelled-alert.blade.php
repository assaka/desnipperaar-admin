@component('emails._layout', ['title' => 'Order geannuleerd '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Order geannuleerd.</h1>

<p>Opdracht
<strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>
van <strong>{{ $order->customer_name }}</strong>@if ($order->customer?->company) ({{ $order->customer->company }})@endif
gaat niet door@if ($canceledBy), geannuleerd door <strong>{{ $canceledBy->name }}</strong>@endif.</p>

<div style="background:#F7F7F4;padding:14px 16px;border-left:3px solid #767676;margin:20px 0;">
    <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.1em;text-transform:uppercase;color:#555;margin-bottom:6px;">Reden</div>
    <div style="font-weight:900;font-size:14pt;line-height:1.3;">
        {{ $order->cancel_reason ?: 'geen reden opgegeven' }}
    </div>
    <div style="font-size:12px;color:#555;margin-top:8px;padding-top:8px;border-top:1px dashed #CCC;">
        {{ $order->canceled_at?->format('d-m-Y H:i') ?? now()->format('d-m-Y H:i') }}
        @if ($order->pickup_date)
            &middot; vervallen ophaalmoment {{ $order->pickup_date->format('d-m-Y') }}{{ $order->pickup_window ? ' ('.$order->pickup_window.')' : '' }}
        @endif
    </div>
</div>

<h2 style="font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:20px 0 6px;color:#555;">Wat er is meegegaan</h2>
<div style="font-size:14px;line-height:1.7;">
    @if ($droppedBons)
        {{ $droppedBons }} nog niet gereden bon(nen) verwijderd, die ritten staan niet meer in de planning.<br>
    @else
        Er stonden geen ongereden ritten open.<br>
    @endif
    @if ($voidedInvoices)
        {{ $voidedInvoices }} openstaande factu{{ $voidedInvoices === 1 ? 'ur' : 'ren' }} vervallen verklaard.<br>
    @else
        Er stond geen openstaande factuur op deze order.<br>
    @endif
    {{ $customerNotified ? 'De klant heeft een annuleringsmail gekregen.' : 'De klant is NIET gemaild.' }}
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

<p style="font-size:12px;color:#555;">De order blijft staan met de reden erbij. Gaat het toch door, maak dan een nieuwe order aan; een geannuleerde order wordt niet meer gepland of gefactureerd.</p>
@endcomponent
