@component('emails.es._layout', ['title' => 'Suscripción cancelada '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Su suscripción ha sido cancelada.</h1>

<p>Estimado/a {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Hemos procesado su cancelación. Su suscripción <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong> sigue vigente hasta el <strong>{{ $endsOn->format('d-m-Y') }}</strong> incluido.</p>

<p>Hasta esa fecha recogemos según el calendario habitual y la suscripción se factura con normalidad. Después, la facturación se detiene.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Vigente hasta el</td><td><strong>{{ $endsOn->format('d-m-Y') }}</strong></td></tr>
    @if ($lastPickup)
        <tr><td style="background:#F5F5F5;font-weight:700;">Última recogida</td><td>{{ $lastPickup->pickup_date->format('d-m-Y') }}</td></tr>
    @endif
    @if ($returnCost)
        <tr><td style="background:#F5F5F5;font-weight:700;">Costes de retorno</td><td>€ {{ number_format($returnCost, 2, ',', '.') }}</td></tr>
    @endif
</table>

@if ($returnCost)
<p style="background:#FFF8E1;border-left:4px solid #F5C518;padding:12px 14px;">
    Como cancela Flex antes de doce meses, se aplica un coste logístico de retorno único de € {{ number_format($returnCost, 2, ',', '.') }} sin IVA. Es el coste real del viaje de vuelta, no una penalización. El importe aparece en su última factura.
</p>
@endif

<p>Recogeremos el contenedor en la fecha de finalización o después. No tiene que hacer nada más.</p>

<p>¿Quiere volver a empezar más adelante? Avísenos y lo dejamos todo listo de nuevo.</p>

<p>Un cordial saludo,<br>Team DeSnipperaar</p>
@endcomponent
