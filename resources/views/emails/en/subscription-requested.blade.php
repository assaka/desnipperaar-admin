@php
    $freqLabels = ['4w' => 'once every 4 weeks', '2w' => 'once every 2 weeks', '1w' => 'once a week', '2pw' => 'twice a week'];
    $termLabels = ['flex' => 'Flex (min. 3 months, then monthly)', 'vast' => 'Fixed (12 months)', 'jaar' => 'Annual prepay (12 months upfront)'];
@endphp
@component('emails.en._layout', ['title' => 'Subscription request '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Thank you! Your subscription request has been received.</h1>

<p>Dear {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Your reference is <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
We will confirm your subscription and pickup schedule within one business day.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Container</td><td>240 L sealed roll container</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Frequency</td><td>{{ $freqLabels[$order->sub_freq] ?? $order->sub_freq }}</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Term</td><td>{{ $termLabels[$order->sub_term] ?? $order->sub_term }}</td></tr>
    @if ($order->sub_price_excl_btw)
        <tr><td style="background:#F5F5F5;font-weight:700;">Price</td><td>
            € {{ number_format($order->sub_price_excl_btw, 2, ',', '.') }}
            {{ $order->sub_term === 'jaar' ? 'per year' : 'every 4 weeks' }} (excl. VAT)
        </td></tr>
    @endif
</table>

<p>You do not need to pay anything yet. The subscription only starts once you have approved our confirmation.</p>

<p>Any questions? Just reply to this email.</p>

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
