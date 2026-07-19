@php
    $freqLabels = ['4w' => '1x cada 4 semanas', '2w' => '1x cada 2 semanas', '1w' => '1x por semana', '2pw' => '2x por semana'];
    $termLabels = ['flex' => 'Flex (mín. 3 meses, luego mensual)', 'vast' => 'Fijo (12 meses)', 'jaar' => 'Pago anual (12 meses por adelantado)'];
    $per = $order->sub_term === 'jaar' ? 'al año' : 'al mes';
@endphp
@component('emails.es._layout', ['title' => 'Suscripción '.$order->order_number.' activa'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Su suscripción está activa.</h1>

<p>Estimado/a {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Gracias por su aprobación. Su suscripción está registrada con la referencia
<strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Contenedor</td><td>Contenedor con ruedas precintado de 240 L</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Frecuencia</td><td>{{ $freqLabels[$order->sub_freq] ?? $order->sub_freq }}</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Duración</td><td>{{ $termLabels[$order->sub_term] ?? $order->sub_term }}</td></tr>
    @if ($order->sub_price_excl_btw)
        <tr><td style="background:#F5F5F5;font-weight:700;">Precio</td><td>
            <strong>€ {{ number_format($order->sub_price_excl_btw, 2, ',', '.') }}</strong> {{ $per }} sin IVA<br>
            <span style="color:#666;">€ {{ number_format($order->sub_price_excl_btw * 1.21, 2, ',', '.') }} {{ $per }} con 21% de IVA</span>
        </td></tr>
    @endif
    @if ($order->sub_active_from)
        <tr><td style="background:#F5F5F5;font-weight:700;">Activa desde</td><td>{{ $order->sub_active_from->format('d-m-Y') }}</td></tr>
    @endif
</table>

<p>Nos pondremos en contacto con usted en un día laborable para colocar el contenedor y acordar
la primera recogida. Después recogemos según este calendario, sin que tenga que hacer nada más.</p>

<p>Recibirá un certificado de destrucción según DIN 66399 en cada recogida.</p>

<p>¿Tiene preguntas o quiere cambiar el calendario? Responda a este correo.</p>

<p>Un cordial saludo,<br>Team DeSnipperaar</p>
@endcomponent
