@php
    $days = [1 => 'maandag', 2 => 'dinsdag', 3 => 'woensdag', 4 => 'donderdag', 5 => 'vrijdag'];
    $newDay = $order->sub_freq === '2pw' ? 'maandag en donderdag' : ($days[$order->subPickupWeekday()] ?? null);
@endphp
@component('emails._layout', ['title' => 'Ophaaldag gewijzigd '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Uw vaste ophaaldag is gewijzigd.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Wij halen uw verzegelde rolcontainer vanaf nu op een andere vaste dag op.
Uw frequentie, prijs en looptijd blijven ongewijzigd.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Was</td><td style="color:#666;text-decoration:line-through;">{{ ucfirst($previous) }}</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Wordt</td><td><strong>{{ ucfirst($newDay) }}</strong></td></tr>
    @if ($nextPickup)
        <tr><td style="background:#F5F5F5;font-weight:700;">Eerstvolgende ophaling</td><td><strong>{{ $nextPickup->format('d-m-Y') }}</strong></td></tr>
    @endif
    <tr><td style="background:#F5F5F5;font-weight:700;">Frequentie</td><td>{{ $order->subFreqLabel() }}</td></tr>
</table>

<p>Zet de container op die dag voor 08:00 op de gebruikelijke plek. Wij sturen u de dag
ervoor nog een herinnering.</p>

<p>Komt deze dag u niet uit? Reply dan op deze email, dan kijken wij naar een andere dag.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
