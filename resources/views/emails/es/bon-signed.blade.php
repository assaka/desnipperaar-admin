@php
    $isBezorg = $bon->mode === 'bezorging';
    $isRetour = $bon->mode === 'retour';
    $isOphaal = ! $isBezorg && ! $isRetour;
    $heading = $isBezorg ? 'Su contenedor ha sido entregado.' : ($isRetour ? 'Su contenedor ha sido recogido.' : 'Sus documentos han sido recogidos.');
    $bonLabel = $isBezorg ? 'Albarán de entrega' : ($isRetour ? 'Albarán de retorno' : 'Albarán de recogida');
@endphp
@component('emails.es._layout', ['title' => $bonLabel.' '.$bon->bon_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">{{ $heading }}</h1>

<p>Hola {{ explode(' ', $bon->order->customer_name)[0] }},</p>

@if ($isBezorg)
    <p>Acabamos de entregar su contenedor con ruedas precintado de 240 L para la suscripción
    <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong>.
    Encontrará el albarán de entrega firmado adjunto en formato PDF. Puede empezar a llenarlo de inmediato.</p>
@elseif ($isRetour)
    <p>Hemos recogido el contenedor para la suscripción
    <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong>.
    Encontrará el albarán de retorno firmado adjunto en formato PDF. Su suscripción queda cerrada.</p>
@else
    <p>La recogida del pedido <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong>
    acaba de completarse. Encontrará el albarán de recogida firmado adjunto en formato PDF.</p>
@endif

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:14px 18px;font-size:13px;">
            <div><strong>Número de albarán:</strong> <span style="font-family:'Courier New',monospace;">{{ $bon->bon_number }}</span></div>
            <div><strong>Fecha:</strong> {{ $bon->picked_up_at?->format('d-m-Y H:i') }}</div>
            @if ($isOphaal && $bon->weight_kg) <div><strong>Peso:</strong> {{ $bon->weight_kg }} kg</div> @endif
            @if ($isOphaal && $bon->seals->count())
                <div><strong>Número de precintos:</strong> {{ $bon->seals->count() }}</div>
            @endif
            <div><strong>Conductor:</strong> {{ $bon->driver_name_snapshot ?? '—' }} (carné ****{{ $bon->driver_license_last4 ?? '—' }})</div>
        </td>
    </tr>
</table>

@if ($isOphaal)
    <p style="font-size:13px;color:#555;">Los números de precinto y el albarán firmado son su prueba de que el material se recogió sellado. Conserve este correo y el PDF en sus registros — junto con el <strong>Certificado de Destrucción</strong> que sigue, forman la pista de auditoría completa.</p>
    <p>Recibirá el certificado en 24 horas, una vez destruido el material.</p>
@elseif ($isBezorg)
    <p>Recogemos periódicamente según su suscripción. Recibe un recordatorio el día antes de cada recogida, y un certificado de destrucción en cada recogida.</p>
@else
    <p>Conserve este albarán firmado en sus registros.</p>
@endif

<p>Un cordial saludo,<br>El equipo de DeSnipperaar</p>
@endcomponent
