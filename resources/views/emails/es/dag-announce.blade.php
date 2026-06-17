@component('emails.es._layout', ['title' => 'DíaDestrucción'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Hoy es el DíaDestrucción.</h1>

<p>Un día al azar cada semana ofrecemos un {{ $pct }}% de descuento. Hoy es ese día. Solo hoy, y solo para usted como suscriptor.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:22px 0;background:#0A0A0A;">
    <tr>
        <td style="padding:22px;text-align:center;">
            <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.14em;text-transform:uppercase;color:#F5C518;margin-bottom:8px;">Su código de descuento</div>
            <div style="font-family:'Courier New',monospace;font-weight:900;font-size:30px;letter-spacing:0.12em;color:#FFFFFF;">{{ $code }}</div>
            <div style="font-size:13px;color:#BBB;margin-top:6px;">{{ $pct }}% de descuento, válido hasta medianoche de hoy</div>
        </td>
    </tr>
</table>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 8px;">
    <tr><td align="center">
        <a href="{{ $orderUrl }}" style="display:inline-block;background:#F5C518;color:#0A0A0A;font-weight:900;font-size:16px;text-decoration:none;padding:16px 32px;">Pedir ahora con un {{ $pct }}% de descuento</a>
    </td></tr>
</table>

<p style="font-size:13px;color:#555;margin-top:20px;">El código se activa automáticamente hoy y caduca a medianoche. Hasta el próximo DíaDestrucción.</p>

<p>Equipo DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    Recibe esto porque se suscribió al DíaDestrucción.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Darse de baja</a>.
</p>
@endcomponent
