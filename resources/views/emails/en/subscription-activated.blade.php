@php
    $freqLabels = ['4w' => 'once every 4 weeks', '2w' => 'once every 2 weeks', '1w' => 'once a week', '2pw' => 'twice a week'];
    $termLabels = ['flex' => 'Flex (min. 3 months, then monthly)', 'vast' => 'Fixed (12 months)', 'jaar' => 'Annual prepay (12 months upfront)'];
    $per = $order->sub_term === 'jaar' ? 'per year' : 'per month';
@endphp
@component('emails.en._layout', ['title' => 'Subscription '.$order->order_number.' active'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Your subscription is active.</h1>

<p>Dear {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Thank you for your approval. Your subscription is registered under reference
<strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Container</td><td>240 L sealed roll container</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Frequency</td><td>{{ $freqLabels[$order->sub_freq] ?? $order->sub_freq }}</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Term</td><td>{{ $termLabels[$order->sub_term] ?? $order->sub_term }}</td></tr>
    @if ($order->sub_price_excl_btw)
        <tr><td style="background:#F5F5F5;font-weight:700;">Price</td><td>
            <strong>€ {{ number_format($order->sub_price_excl_btw, 2, ',', '.') }}</strong> {{ $per }} excl. VAT<br>
            <span style="color:#666;">€ {{ number_format($order->sub_price_excl_btw * 1.21, 2, ',', '.') }} {{ $per }} incl. 21% VAT</span>
        </td></tr>
    @endif
    @if ($order->sub_active_from)
        <tr><td style="background:#F5F5F5;font-weight:700;">Active from</td><td>{{ $order->sub_active_from->format('d-m-Y') }}</td></tr>
    @endif
</table>

<p>We will contact you within one business day to place the container and agree the first
pickup. After that we collect on this schedule, with nothing further needed from you.</p>

<p>You receive a certificate of destruction to DIN 66399 at every pickup.</p>

<p>Any questions, or want to change the schedule? Just reply to this email.</p>

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
