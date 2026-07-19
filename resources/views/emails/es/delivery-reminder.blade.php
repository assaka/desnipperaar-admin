@component('emails.es._layout', ['title' => 'Entregamos su contenedor '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Mañana entregamos su contenedor.</h1>

<p>Estimado/a {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Un breve recordatorio. Mañana, {{ $order->pickup_date->format('d-m-Y') }}, entregamos su
contenedor con ruedas precintado de 240 litros.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Día de entrega</td><td><strong>{{ $order->pickup_date->format('d-m-Y') }}</strong></td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Dirección</td><td>{{ $order->customer_address }}, {{ $order->customer_postcode }} {{ $order->customer_city }}</td></tr>
    @if ($firstPickup)
        <tr><td style="background:#F5F5F5;font-weight:700;">Primera recogida</td><td><strong>{{ $firstPickup->format('d-m-Y') }}</strong></td></tr>
    @endif
    <tr><td style="background:#F5F5F5;font-weight:700;">Referencia</td><td style="font-family:monospace;">{{ $order->order_number }}</td></tr>
</table>

<p>Procure que haya alguien presente o que el lugar sea accesible, así colocamos el contenedor
directamente en el sitio correcto. No hay nada más que preparar.</p>

@if ($firstPickup)
    <p>Puede empezar a llenarlo desde mañana. Lo recogemos por primera vez el
    {{ $firstPickup->format('d-m-Y') }}, y le enviamos un recordatorio el día anterior.</p>
@endif

<p>Recibirá un certificado de destrucción según DIN 66399 en cada recogida.</p>

<p>¿Mañana no le viene bien? Responda a este correo y buscamos otro momento.</p>

<p>Un cordial saludo,<br>Team DeSnipperaar</p>
@endcomponent
