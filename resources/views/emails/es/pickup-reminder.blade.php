@component('emails.es._layout', ['title' => 'Recordatorio de recogida '.$visit->bon_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Mañana pasamos a recoger.</h1>

<p>Estimado/a {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Un breve recordatorio. Mañana, {{ $visit->planned_for->format('d-m-Y') }}, recogemos su contenedor con ruedas precintado.</p>

@if ($shifted)
<p style="background:#FFF8E1;border-left:4px solid #F5C518;padding:12px 14px;">
    Tenga en cuenta. Normalmente venimos el {{ $visit->scheduled_for->format('d-m-Y') }}, pero ese día es esta vez festivo o fin de semana. Por eso vamos el {{ $visit->planned_for->format('d-m-Y') }}. Su día de recogida fijo no cambia.
</p>
@endif

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Fecha de recogida</td><td><strong>{{ $visit->planned_for->format('d-m-Y') }}</strong></td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Dirección</td><td>{{ $order->customer_address }}, {{ $order->customer_postcode }} {{ $order->customer_city }}</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Referencia</td><td style="font-family:monospace;">{{ $visit->bon_number }}</td></tr>
</table>

<p>Coloque el contenedor en el sitio habitual antes de las 08:00 para que nuestro conductor pueda acceder.</p>

<p>Tras la destrucción recibirá su certificado de destrucción según DIN 66399.</p>

<p>¿Mañana no le viene bien? Responda a este correo y buscamos otro momento.</p>

<p>Un cordial saludo,<br>Team DeSnipperaar</p>
@endcomponent
