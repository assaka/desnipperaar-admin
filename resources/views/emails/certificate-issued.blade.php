@component('emails._layout', ['title' => 'Certificaat '.$certificate->certificate_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Uw vertrouwelijke materiaal is vernietigd.</h1>

<p>Beste {{ explode(' ', $certificate->order->customer_name)[0] }},</p>

<p>Hierbij uw certificaat van vernietiging voor order <strong style="font-family:monospace;">{{ $certificate->order->order_number }}</strong>.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Certificaatnummer</td>
        <td style="padding:8px 0;text-align:right;font-family:monospace;font-weight:700;">{{ $certificate->certificate_number }}</td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Datum vernietiging</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">{{ $certificate->destroyed_at?->format('d-m-Y') }}</td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Methode</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">{{ $certificate->destruction_method }}</td></tr>
    @if ($certificate->weight_kg_final)
        <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Eindgewicht</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">{{ $certificate->weight_kg_final }} kg</td></tr>
    @endif
</table>

<p style="background:#F7F7F4;border-left:3px solid #F5C518;padding:12px 14px;font-size:13px;">Uw certificaat van vernietiging zit als <strong>PDF-bijlage</strong> bij deze e-mail. Bewaar het in uw dossier.</p>

<p style="font-size:12px;color:#555;">Dit certificaat is geldig als bewijs van vernietiging voor AVG-, Wwft-,
AFM- en DNB-toezicht. Bewaar deze mail of de PDF in uw dossier.</p>

<p>Met vriendelijke groet,<br>DeSnipperaar</p>
@endcomponent
