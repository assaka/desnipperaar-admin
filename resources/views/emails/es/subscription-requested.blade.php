@php
    $freqLabels = ['4w' => '1x cada 4 semanas', '2w' => '1x cada 2 semanas', '1w' => '1x por semana', '2pw' => '2x por semana'];
    $termLabels = ['flex' => 'Flex (mín. 3 meses, luego mensual)', 'vast' => 'Fijo (12 meses)', 'jaar' => 'Pago anual (12 meses por adelantado)'];
@endphp
@component('emails.es._layout', ['title' => 'Solicitud de suscripción '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">¡Gracias! Hemos recibido su solicitud de suscripción.</h1>

<p>Estimado/a {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Su referencia es <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
Confirmaremos su suscripción y su calendario de recogida en un día laborable.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Contenedor</td><td>Contenedor con ruedas precintado de 240 L</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Frecuencia</td><td>{{ $freqLabels[$order->sub_freq] ?? $order->sub_freq }}</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Duración</td><td>{{ $termLabels[$order->sub_term] ?? $order->sub_term }}</td></tr>
    @if ($order->sub_price_excl_btw)
        <tr><td style="background:#F5F5F5;font-weight:700;">Precio</td><td>
            € {{ number_format($order->sub_price_excl_btw, 2, ',', '.') }}
            {{ $order->sub_term === 'jaar' ? 'al año' : 'al mes' }} (sin IVA)
        </td></tr>
    @endif
</table>

<p>Todavía no tiene que pagar nada. La suscripción solo comienza una vez que haya aprobado nuestra confirmación.</p>

<p>¿Tiene alguna pregunta? Responda a este correo.</p>

<p>Un cordial saludo,<br>Team DeSnipperaar</p>
@endcomponent
