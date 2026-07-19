@component('emails._layout', ['title' => 'Wij brengen uw container '.$visit->bon_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Morgen brengen wij uw container.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Een korte herinnering. Morgen, {{ $visit->planned_for->format('d-m-Y') }}, brengen wij uw
verzegelde rolcontainer van 240 liter.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Bezorgdag</td><td><strong>{{ $visit->planned_for->format('d-m-Y') }}</strong></td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Adres</td><td>{{ $order->customer_address }}, {{ $order->customer_postcode }} {{ $order->customer_city }}</td></tr>
    @if ($firstPickup)
        <tr><td style="background:#F5F5F5;font-weight:700;">Eerste ophaling</td><td><strong>{{ $firstPickup->format('d-m-Y') }}</strong></td></tr>
    @endif
    <tr><td style="background:#F5F5F5;font-weight:700;">Referentie</td><td style="font-family:monospace;">{{ $visit->bon_number }}</td></tr>
</table>

<p>Zorg dat er iemand aanwezig is of dat de plek bereikbaar is, dan zetten wij de container
meteen op de goede plaats. U hoeft verder niets voor te bereiden.</p>

@if ($firstPickup)
    <p>Vanaf morgen kunt u hem vullen. Wij halen hem voor het eerst op
    {{ $firstPickup->format('d-m-Y') }} op, en sturen u de dag ervoor nog een herinnering.</p>
@endif

<p>Bij elke ophaling ontvangt u een vernietigingscertificaat volgens DIN 66399.</p>

<p>Komt het morgen niet uit? Reply dan op deze email, dan zoeken wij een ander moment.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
