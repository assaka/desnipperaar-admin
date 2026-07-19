@php
    $per = $order->sub_term === 'jaar' ? 'per jaar' : 'per 4 weken';
    $days = [1 => 'maandag', 2 => 'dinsdag', 3 => 'woensdag', 4 => 'donderdag', 5 => 'vrijdag'];
    $pickupDay = $order->sub_freq === '2pw'
        ? 'maandag en donderdag'
        : ($days[$order->subPickupWeekday()] ?? null);
    $next = $order->nextPickupDate();
@endphp
@component('emails._layout', ['title' => 'Abonnement '.$order->order_number.' actief'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Uw abonnement is actief.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Bedankt voor uw akkoord. Uw abonnement staat bij ons klaar onder referentie
<strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Container</td><td>240 L verzegelde rolcontainer</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Frequentie</td><td>{{ $order->subFreqLabel() }}</td></tr>
    @if ($pickupDay)
        <tr><td style="background:#F5F5F5;font-weight:700;">Vaste ophaaldag</td><td>{{ ucfirst($pickupDay) }}</td></tr>
    @endif
    <tr><td style="background:#F5F5F5;font-weight:700;">Bezorgdag container</td><td><strong>{{ $order->sub_active_from->format('d-m-Y') }}</strong></td></tr>
    @if ($next)
        <tr><td style="background:#F5F5F5;font-weight:700;">Eerste ophaling</td><td><strong>{{ $next->format('d-m-Y') }}</strong></td></tr>
    @endif
    <tr><td style="background:#F5F5F5;font-weight:700;">Looptijd</td><td>{{ $order->subTermLabel() }}</td></tr>
    @if ($order->sub_price_excl_btw)
        <tr><td style="background:#F5F5F5;font-weight:700;">Prijs</td><td>
            <strong>€ {{ number_format($order->sub_price_excl_btw, 2, ',', '.') }}</strong> {{ $per }} excl. btw<br>
            <span style="color:#666;">€ {{ number_format($order->sub_price_excl_btw * 1.21, 2, ',', '.') }} {{ $per }} incl. 21% btw</span>
        </td></tr>
    @endif
</table>

<p>Wij brengen de container op {{ $order->sub_active_from->format('d-m-Y') }}. Vanaf die dag loopt
uw abonnement. U heeft daarna rustig de tijd om hem te vullen.
@if ($next)
De eerste ophaling is pas op {{ $next->format('d-m-Y') }}.
@endif
Daarna halen wij op volgens dit schema, zonder dat u er verder iets voor hoeft te doen.</p>

<p>Wij sturen u telkens één dag voor de ophaling een herinnering, zodat u de container op tijd
klaar kunt zetten. Wijkt een ophaaldag een keer af, bijvoorbeeld rond een feestdag, dan staat de
juiste datum in die herinnering.</p>

<p>Bij elke ophaling ontvangt u een vernietigingscertificaat volgens DIN 66399.</p>

<p>Heeft u vragen of wilt u het schema wijzigen? Reply dan op deze email.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
