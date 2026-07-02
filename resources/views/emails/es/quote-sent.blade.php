@component('emails.es._layout', ['title' => 'Presupuesto '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Su presupuesto está listo.</h1>

<p>Hola {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Aquí tiene nuestro presupuesto para el pedido <strong style="font-family:monospace;">{{ $order->order_number }}</strong>.</p>

@if ($order->quoted_amount_excl_btw)
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Importe sin IVA</td>
        <td style="padding:8px 0;text-align:right;font-weight:900;font-size:18px;font-family:monospace;">
            € {{ number_format($order->quoted_amount_excl_btw, 2, ',', '.') }}
        </td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Importe con IVA 21%</td>
        <td style="padding:8px 0;text-align:right;font-family:monospace;border-top:1px solid #EEE;">
            € {{ number_format($order->quoted_amount_excl_btw * 1.21, 2, ',', '.') }}
        </td></tr>
    @if ($order->quote_valid_until)
        <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Válido hasta</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">
                {{ $order->quote_valid_until->format('d-m-Y') }}
            </td></tr>
    @endif
</table>
@endif

<div style="white-space:pre-line;font-size:14px;line-height:1.6;background:#F7F7F4;padding:14px;border-left:3px solid #F5C518;margin:16px 0;">{{ $order->quote_body }}</div>

@if ($order->quoted_amount_excl_btw)
<p style="margin:24px 0;text-align:center;">
    <a href="{{ $acceptUrl }}"
       style="display:inline-block;background:#0A0A0A;color:#F5C518;padding:14px 28px;font-weight:900;font-size:16px;text-decoration:none;text-transform:uppercase;letter-spacing:0.05em;">
        Revisar y aceptar el presupuesto →
    </a>
</p>

<p style="font-size:12px;color:#555;">Este enlace es personal y exclusivo de su presupuesto. Al hacer clic en el botón
y elegir <strong>Aceptar</strong> en la página siguiente, formaliza un acuerdo por el importe indicado arriba.
Si no hace clic, no tiene ninguna obligación.</p>
@endif

<p>Un cordial saludo,<br>El equipo de DeSnipperaar</p>
@endcomponent
