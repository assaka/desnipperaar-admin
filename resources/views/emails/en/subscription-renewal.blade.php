@php
    $freqLabels = ['4w' => 'once every 4 weeks', '2w' => 'once every 2 weeks', '1w' => 'once a week', '2pw' => 'twice a week'];
@endphp
@component('emails.en._layout', ['title' => 'Subscription '.$order->order_number.' term ending'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Your term ends on {{ $renewalDate->format('d-m-Y') }}.</h1>

<p>Dear {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Your subscription <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>
reaches the end of its current term in about a month. Your container stays where it is and we
keep collecting. Only the terms change.</p>

<p style="background:#F5F5F5;border-left:4px solid #F5C518;padding:12px 14px;">
    <strong>Do nothing?</strong> From {{ $renewalDate->copy()->addDay()->format('d-m-Y') }} your subscription
    continues at
    @if ($monthlyPrice)
        € {{ number_format($monthlyPrice, 2, ',', '.') }} every 4 weeks excl. VAT, our discounted rate,
    @endif
    and you can stop at any time. No further commitment.
</p>

<p>Want something else? Just reply to this email and we will arrange it.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    @if ($yearlyPrice)
        <tr>
            <td style="background:#F5F5F5;font-weight:700;vertical-align:top;">Another year upfront</td>
            <td>€ {{ number_format($yearlyPrice, 2, ',', '.') }} per year excl. VAT. The cheapest option, twelve months paid in one go.</td>
        </tr>
    @endif
    <tr>
        <td style="background:#F5F5F5;font-weight:700;vertical-align:top;">Monthly (discounted rate)</td>
        <td>
            @if ($monthlyPrice)
                € {{ number_format($monthlyPrice, 2, ',', '.') }} every 4 weeks excl. VAT.
            @endif
            Cancel any time. This happens automatically if you do not reply.
        </td>
    </tr>
    <tr>
        <td style="background:#F5F5F5;font-weight:700;vertical-align:top;">Stop</td>
        <td>Let us know and we collect the container after {{ $renewalDate->format('d-m-Y') }}. No return costs.</td>
    </tr>
</table>

<p>Current frequency: {{ $freqLabels[$order->sub_freq] ?? $order->sub_freq }}. Would you like more or fewer
collections? You can change that at this transition free of charge.</p>

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
