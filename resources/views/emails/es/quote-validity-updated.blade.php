@php $ref = $order->quote_reference ?? $order->order_number; @endphp
@component('emails._layout', ['title' => 'Presupuesto '.$ref])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">La validez de su presupuesto se ha actualizado.</h1>

<p>Hola {{ explode(' ', $order->customer_name)[0] }},</p>

<p>La validez de nuestro presupuesto <strong style="font-family:monospace;">{{ $ref }}</strong> se ha ampliado. Todavía puede aceptarlo hasta la nueva fecha indicada abajo.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    @if ($order->quoted_amount_excl_btw)
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Importe sin IVA</td>
        <td style="padding:8px 0;text-align:right;font-weight:900;font-size:18px;font-family:monospace;">
            € {{ number_format($order->quoted_amount_excl_btw, 2, ',', '.') }}
        </td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Importe con IVA 21%</td>
        <td style="padding:8px 0;text-align:right;font-family:monospace;border-top:1px solid #EEE;">
            € {{ number_format($order->quoted_amount_excl_btw * 1.21, 2, ',', '.') }}
        </td></tr>
    @endif
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Nueva validez hasta</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">
            {{ $order->quote_valid_until->format('d-m-Y') }}
        </td></tr>
</table>

<p style="margin:24px 0;text-align:center;">
    <a href="{{ $acceptUrl }}"
       style="display:inline-block;background:#0A0A0A;color:#F5C518;padding:14px 28px;font-weight:900;font-size:16px;text-decoration:none;text-transform:uppercase;letter-spacing:0.05em;">
        Ver el presupuesto →
    </a>
</p>

<p style="font-size:12px;color:#555;">Este enlace es personal y único para su presupuesto. En la página siguiente verá todos los detalles. Rellena su dirección y hace clic en <strong>Realizar pedido</strong>. Solo entonces formaliza un acuerdo por el importe indicado arriba. Si no hace clic, no queda obligado a nada.</p>

<p style="font-size:12px;color:#555;">¿Alguna pregunta o cambio? Responda a este correo. <strong>No cambie el asunto</strong> para que su mensaje se añada automáticamente a su presupuesto.</p>

<p>Un saludo,<br>Equipo DeSnipperaar</p>
@endcomponent
