@php
    $freqLabels = ['4w' => 'once every 4 weeks', '2w' => 'once every 2 weeks', '1w' => 'once a week', '2pw' => 'twice a week'];
    $termLabels = ['flex' => 'Flex (min. 3 months, then monthly)', 'vast' => 'Fixed (12 months)', 'jaar' => 'Annual prepay (12 months upfront)'];
    $per = $order->sub_term === 'jaar' ? 'per year' : 'every 4 weeks';
    $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'];
    $pickupDay = $order->sub_freq === '2pw'
        ? 'Monday and Thursday'
        : ($days[$order->subPickupWeekday()] ?? null);
    $next = $order->nextPickupDate();
@endphp
@component('emails.en._layout', ['title' => 'Subscription '.$order->order_number.' active'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Your subscription is active.</h1>

<p>Dear {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Thank you for your approval. Your subscription is registered under reference
<strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Container</td><td>240 L sealed roll container</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Frequency</td><td>{{ $freqLabels[$order->sub_freq] ?? $order->sub_freq }}</td></tr>
    @if ($pickupDay)
        <tr><td style="background:#F5F5F5;font-weight:700;">Fixed pickup day</td><td>{{ ucfirst($pickupDay) }}</td></tr>
    @endif
    <tr><td style="background:#F5F5F5;font-weight:700;">Container delivered</td><td><strong>{{ $order->sub_active_from->format('d-m-Y') }}</strong></td></tr>
    @if ($next)
        <tr><td style="background:#F5F5F5;font-weight:700;">First pickup</td><td><strong>{{ $next->format('d-m-Y') }}</strong></td></tr>
    @endif
    <tr><td style="background:#F5F5F5;font-weight:700;">Term</td><td>{{ $termLabels[$order->sub_term] ?? $order->sub_term }}</td></tr>
    @if ($order->sub_price_excl_btw)
        <tr><td style="background:#F5F5F5;font-weight:700;">Price</td><td>
            <strong>€ {{ number_format($order->sub_price_excl_btw, 2, ',', '.') }}</strong> {{ $per }} excl. VAT<br>
            <span style="color:#666;">€ {{ number_format($order->sub_price_excl_btw * 1.21, 2, ',', '.') }} {{ $per }} incl. 21% VAT</span>
        </td></tr>
    @endif
</table>

<p>We deliver the container on {{ $order->sub_active_from->format('d-m-Y') }}. Your subscription
starts on that day. You then have time to fill it.
@if ($next)
The first pickup is not until {{ $next->format('d-m-Y') }}.
@endif
After that we collect on this schedule, with nothing further needed from you.</p>

<p>We send you a reminder one day before every pickup, so you can put the container out in time.
If a pickup day ever differs, around a public holiday for example, the correct date is in that
reminder.</p>

<p>You receive a certificate of destruction to DIN 66399 at every pickup.</p>

<p style="font-size:13px;color:#555;">If the container is not out on the pickup day, that
collection lapses and your subscription simply continues. You lose nothing: at the next pickup we
take it fuller. If we fail to come through our own fault, we credit that period or collect again
at no charge.</p>

<p>Any questions, or want to change the schedule? Just reply to this email.</p>

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
