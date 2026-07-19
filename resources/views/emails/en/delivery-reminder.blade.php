@component('emails.en._layout', ['title' => 'We deliver your container '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">We deliver your container tomorrow.</h1>

<p>Dear {{ explode(' ', $order->customer_name)[0] }},</p>

<p>A short reminder. Tomorrow, {{ $order->pickup_date->format('d-m-Y') }}, we deliver your sealed
240 litre roll container.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Delivery day</td><td><strong>{{ $order->pickup_date->format('d-m-Y') }}</strong></td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Address</td><td>{{ $order->customer_address }}, {{ $order->customer_postcode }} {{ $order->customer_city }}</td></tr>
    @if ($firstPickup)
        <tr><td style="background:#F5F5F5;font-weight:700;">First pickup</td><td><strong>{{ $firstPickup->format('d-m-Y') }}</strong></td></tr>
    @endif
    <tr><td style="background:#F5F5F5;font-weight:700;">Reference</td><td style="font-family:monospace;">{{ $order->order_number }}</td></tr>
</table>

<p>Please make sure someone is present or that the spot is accessible, so we can put the container
straight in the right place. Nothing else to prepare.</p>

@if ($firstPickup)
    <p>You can start filling it from tomorrow. We collect it for the first time on
    {{ $firstPickup->format('d-m-Y') }}, and we send you a reminder the day before.</p>
@endif

<p>You receive a certificate of destruction to DIN 66399 at every pickup.</p>

<p>Does tomorrow not suit you? Just reply to this email and we will find another moment.</p>

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
