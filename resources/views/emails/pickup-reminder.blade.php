@component('emails._layout', ['title' => 'Herinnering ophaling '.$visit->bon_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Wij halen morgen op.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Een korte herinnering. Morgen, {{ $visit->planned_for->format('d-m-Y') }}, halen wij uw verzegelde rolcontainer op.</p>

@if ($shifted)
<p style="background:#FFF8E1;border-left:4px solid #F5C518;padding:12px 14px;">
    Let op. Normaal komen wij op {{ $visit->scheduled_for->format('d-m-Y') }}, maar die dag is dit keer een feestdag of een weekenddag. Wij komen daarom op {{ $visit->planned_for->format('d-m-Y') }}. Uw vaste ophaaldag verandert hierdoor niet.
</p>
@endif

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Ophaaldatum</td><td><strong>{{ $visit->planned_for->format('d-m-Y') }}</strong></td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Adres</td><td>{{ $order->customer_address }}, {{ $order->customer_postcode }} {{ $order->customer_city }}</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Referentie</td><td style="font-family:monospace;">{{ $visit->bon_number }}</td></tr>
</table>

<p>Zet de container voor 08:00 op de gebruikelijke plek, dan kan onze chauffeur erbij.</p>

<p>Na de vernietiging ontvangt u het vernietigingscertificaat volgens DIN 66399.</p>

<p>Komt het morgen niet uit? Reply dan op deze email, dan zoeken wij een ander moment.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
