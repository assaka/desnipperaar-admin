@php
    $days = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes'];
    $newDay = $order->sub_freq === '2pw' ? 'lunes y jueves' : ($days[$order->subPickupWeekday()] ?? null);
    $freqLabels = ['4w' => '1x cada 4 semanas', '2w' => '1x cada 2 semanas', '1w' => '1x por semana', '2pw' => '2x por semana'];
@endphp
@component('emails.es._layout', ['title' => 'Día de recogida modificado '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Su día de recogida fijo ha cambiado.</h1>

<p>Estimado/a {{ explode(' ', $order->customer_name)[0] }},</p>

<p>A partir de ahora recogemos su contenedor con ruedas precintado otro día fijo.
Su frecuencia, precio y duración no cambian.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Antes</td><td style="color:#666;text-decoration:line-through;">{{ ucfirst($previous) }}</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Ahora</td><td><strong>{{ ucfirst($newDay) }}</strong></td></tr>
    @if ($nextPickup)
        <tr><td style="background:#F5F5F5;font-weight:700;">Próxima recogida</td><td><strong>{{ $nextPickup->format('d-m-Y') }}</strong></td></tr>
    @endif
    <tr><td style="background:#F5F5F5;font-weight:700;">Frecuencia</td><td>{{ $freqLabels[$order->sub_freq] ?? $order->sub_freq }}</td></tr>
</table>

<p>Coloque el contenedor antes de las 08:00 ese día, en el sitio habitual. Le enviamos un
recordatorio el día anterior.</p>

<p>¿Este día no le viene bien? Responda a este correo y buscaremos otro día.</p>

<p>Un cordial saludo,<br>Team DeSnipperaar</p>
@endcomponent
