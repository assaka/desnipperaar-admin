@php
    $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'];
    $newDay = $order->sub_freq === '2pw' ? 'Monday and Thursday' : ($days[$order->subPickupWeekday()] ?? null);
    $freqLabels = ['4w' => 'once every 4 weeks', '2w' => 'once every 2 weeks', '1w' => 'once a week', '2pw' => 'twice a week'];
@endphp
@component('emails.en._layout', ['title' => 'Pickup day changed '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Your fixed pickup day has changed.</h1>

<p>Dear {{ explode(' ', $order->customer_name)[0] }},</p>

<p>From now on we collect your sealed roll container on a different fixed day.
Your frequency, price and term stay the same.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Was</td><td style="color:#666;text-decoration:line-through;">{{ ucfirst($previous) }}</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Now</td><td><strong>{{ ucfirst($newDay) }}</strong></td></tr>
    @if ($nextPickup)
        <tr><td style="background:#F5F5F5;font-weight:700;">Next pickup</td><td><strong>{{ $nextPickup->format('d-m-Y') }}</strong></td></tr>
    @endif
    <tr><td style="background:#F5F5F5;font-weight:700;">Frequency</td><td>{{ $freqLabels[$order->sub_freq] ?? $order->sub_freq }}</td></tr>
</table>

<p>Please put the container out before 08:00 on that day, in the usual spot. We send you a
reminder the day before.</p>

<p>Does this day not suit you? Just reply to this email and we will look at another day.</p>

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
