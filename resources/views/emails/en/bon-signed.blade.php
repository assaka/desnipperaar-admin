@component('emails.en._layout', ['title' => 'Pickup receipt '.$bon->bon_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Your documents have been collected.</h1>

<p>Dear {{ explode(' ', $bon->order->customer_name)[0] }},</p>

<p>The pickup for order <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong> has just been completed. Please find the signed pickup receipt attached as a PDF.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:14px 18px;font-size:13px;">
            <div><strong>Receipt number:</strong> <span style="font-family:'Courier New',monospace;">{{ $bon->bon_number }}</span></div>
            <div><strong>Date:</strong> {{ $bon->picked_up_at?->format('d-m-Y H:i') }}</div>
            @if ($bon->weight_kg) <div><strong>Weight:</strong> {{ $bon->weight_kg }} kg</div> @endif
            @if ($bon->seals->count())
                <div><strong>Number of seals:</strong> {{ $bon->seals->count() }}</div>
            @endif
            <div><strong>Driver:</strong> {{ $bon->driver_name_snapshot ?? '—' }} (licence ****{{ $bon->driver_license_last4 ?? '—' }})</div>
        </td>
    </tr>
</table>

<p style="font-size:13px;color:#555;">The seal numbers and the signed receipt are your proof that the material was collected sealed. Keep this email and PDF in your records — together with the <strong>Certificate of Destruction</strong> that follows, they form the complete audit trail.</p>

<p>You will receive the certificate within 24 hours, once the material has been destroyed.</p>

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
