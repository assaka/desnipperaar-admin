@component('emails.en._layout', ['title' => 'Certificate '.$certificate->certificate_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Your confidential material has been destroyed.</h1>

<p>Dear {{ explode(' ', $certificate->order->customer_name)[0] }},</p>

<p>Please find your certificate of destruction for order <strong style="font-family:monospace;">{{ $certificate->order->order_number }}</strong>.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Certificate number</td>
        <td style="padding:8px 0;text-align:right;font-family:monospace;font-weight:700;">{{ $certificate->certificate_number }}</td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Date of destruction</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">{{ $certificate->destroyed_at?->format('d-m-Y') }}</td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Method</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">{{ $certificate->destruction_method }}</td></tr>
    @if ($certificate->weight_kg_final)
        <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Final weight</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">{{ $certificate->weight_kg_final }} kg</td></tr>
    @endif
</table>

<p style="background:#F7F7F4;border-left:3px solid #F5C518;padding:12px 14px;font-size:13px;">Your certificate of destruction is attached to this email as a <strong>PDF</strong>. Keep it in your records.</p>

<p style="font-size:12px;color:#555;">This certificate serves as proof of destruction for GDPR, AML (Wwft),
AFM and DNB supervision. Keep this email or the PDF in your records.</p>

<p>Kind regards,<br>DeSnipperaar</p>
@endcomponent
