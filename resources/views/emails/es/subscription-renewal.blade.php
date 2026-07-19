@php
    $freqLabels = ['4w' => '1x cada 4 semanas', '2w' => '1x cada 2 semanas', '1w' => '1x por semana', '2pw' => '2x por semana'];
@endphp
@component('emails.es._layout', ['title' => 'Suscripción '.$order->order_number.' vence pronto'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Su plazo termina el {{ $renewalDate->format('d-m-Y') }}.</h1>

<p>Estimado/a {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Su suscripción <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>
llega al final de su plazo actual dentro de un mes aproximadamente. Su contenedor se queda donde
está y seguimos recogiendo. Solo cambian las condiciones.</p>

<p style="background:#F5F5F5;border-left:4px solid #F5C518;padding:12px 14px;">
    <strong>¿No hace nada?</strong> Desde el {{ $renewalDate->copy()->addDay()->format('d-m-Y') }} su suscripción
    continúa mensualmente por
    @if ($monthlyPrice)
        € {{ number_format($monthlyPrice, 2, ',', '.') }} al mes sin IVA,
    @endif
    y puede parar cuando quiera. Sin ningún compromiso.
</p>

<p>¿Prefiere otra cosa? Responda a este correo y lo gestionamos.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    @if ($yearlyPrice)
        <tr>
            <td style="background:#F5F5F5;font-weight:700;vertical-align:top;">Otro año por adelantado</td>
            <td>€ {{ number_format($yearlyPrice, 2, ',', '.') }} al año sin IVA. La opción más económica, doce meses de una vez.</td>
        </tr>
    @endif
    <tr>
        <td style="background:#F5F5F5;font-weight:700;vertical-align:top;">Mensual</td>
        <td>
            @if ($monthlyPrice)
                € {{ number_format($monthlyPrice, 2, ',', '.') }} al mes sin IVA.
            @endif
            Cancelable en cualquier momento. Esto ocurre automáticamente si no responde.
        </td>
    </tr>
    <tr>
        <td style="background:#F5F5F5;font-weight:700;vertical-align:top;">Parar</td>
        <td>Avísenos y recogemos el contenedor después del {{ $renewalDate->format('d-m-Y') }}. Sin costes de retorno.</td>
    </tr>
</table>

<p>Frecuencia actual: {{ $freqLabels[$order->sub_freq] ?? $order->sub_freq }}. ¿Quiere más o menos recogidas?
Puede cambiarlo sin coste en esta transición.</p>

<p>Un cordial saludo,<br>Team DeSnipperaar</p>
@endcomponent
