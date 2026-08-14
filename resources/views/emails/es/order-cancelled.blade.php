@component('emails._layout', ['title' => 'Pedido '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Su pedido ha sido cancelado.</h1>

<p>Hola {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Su pedido <strong style="font-family:monospace;">{{ $order->order_number }}</strong> ha sido cancelado. No pasaremos a recogerlo y no recibirá ninguna factura.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Pedido</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;font-family:monospace;">{{ $order->order_number }}</td></tr>
    @if ($order->pickup_date)
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Cita cancelada</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">
            {{ $order->pickup_date->format('d-m-Y') }}{{ $order->pickup_window ? ' ('.$order->pickup_window.')' : '' }}
        </td></tr>
    @endif
    @if ($reason)
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Motivo</td>
        <td style="padding:8px 0;text-align:right;border-top:1px solid #EEE;">{{ $reason }}</td></tr>
    @endif
</table>

@if ($order->pickup_date)
<p>No tiene que preparar nada ese día.</p>
@endif

<p>¿No es correcto, o desea una nueva cita? Responda simplemente a este correo. <strong>Mantenga el asunto sin cambios</strong> y su mensaje se añadirá automáticamente a este pedido.</p>

<p>Un cordial saludo,<br>El equipo de DeSnipperaar</p>
@endcomponent
