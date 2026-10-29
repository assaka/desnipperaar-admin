@component('emails.es._layout', ['title' => 'Certificado '.$certificate->certificate_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Su material confidencial ha sido destruido.</h1>

<p>Hola {{ explode(' ', $certificate->order->customer_name)[0] }},</p>

<p>Aquí tiene su certificado de destrucción para el pedido <strong style="font-family:monospace;">{{ $certificate->order->order_number }}</strong>.</p>

@if (! empty($corrected))
<p style="background:#FFF4D6;border-left:3px solid #F5C518;padding:12px 14px;font-size:13px;"><strong>Versión corregida.</strong>
@if ($certificate->isMobileQuote() && ! $certificate->isOnsite())
Su certificado se genera automáticamente. Su presupuesto incluía la opción de destrucción móvil in situ, por lo que la versión anterior indicaba un lugar de destrucción incorrecto. Su material se destruyó en nuestro depósito de Amsterdam-Noord. Ese lugar figura ahora en el certificado. Este ejemplar sustituye al certificado que recibió anteriormente.
@else
Este certificado sustituye al ejemplar que recibió anteriormente. Utilice a partir de ahora esta versión.
@endif
</p>
@endif

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Número de certificado</td>
        <td style="padding:8px 0 8px 12px;text-align:right;white-space:nowrap;font-family:monospace;font-weight:700;">{{ $certificate->certificate_number }}</td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Fecha de destrucción</td>
        <td style="padding:8px 0 8px 12px;text-align:right;white-space:nowrap;font-weight:700;border-top:1px solid #EEE;">{{ $certificate->destroyed_at?->format('d-m-Y') }}</td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Método</td>
        <td style="padding:8px 0 8px 12px;text-align:right;white-space:nowrap;font-weight:700;border-top:1px solid #EEE;">{{ $certificate->destruction_method }}</td></tr>
    @if ($certificate->weight_kg_final)
        <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Peso final</td>
            <td style="padding:8px 0 8px 12px;text-align:right;white-space:nowrap;font-weight:700;border-top:1px solid #EEE;">{{ $certificate->weight_kg_final }} kg</td></tr>
    @endif
</table>

<p style="background:#F7F7F4;border-left:3px solid #F5C518;padding:12px 14px;font-size:13px;">Su certificado de destrucción está adjunto a este correo en formato <strong>PDF</strong>. Consérvelo en sus registros.</p>

<p style="font-size:12px;color:#555;">Este certificado sirve como prueba de destrucción para el RGPD, la prevención del blanqueo (Wwft),
y la supervisión de la AFM y el DNB. Conserve este correo o el PDF en sus registros.</p>

<p>Un cordial saludo,<br>DeSnipperaar</p>
@endcomponent
