@php
    $isBezorg = $bon->mode === 'bezorging';
    $isRetour = $bon->mode === 'retour';
    $isOphaal = ! $isBezorg && ! $isRetour;
    $heading = $isBezorg ? 'Your container has been delivered.' : ($isRetour ? 'Your container has been collected.' : 'Your documents have been collected.');
    $bonLabel = $isBezorg ? 'Delivery receipt' : ($isRetour ? 'Return receipt' : 'Pickup receipt');
@endphp
@component('emails.en._layout', ['title' => $bonLabel.' '.$bon->bon_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">{{ $heading }}</h1>

<p>Dear {{ explode(' ', $bon->order->customer_name)[0] }},</p>

@if ($isBezorg)
    <p>We have just delivered your sealed 240 L roll container for subscription
    <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong>.
    Please find the signed delivery receipt attached as a PDF. You can start filling the container right away.</p>
@elseif ($isRetour)
    <p>We have collected the container from you for subscription
    <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong>.
    Please find the signed return receipt attached as a PDF. Your subscription is now closed.</p>
@else
    <p>The pickup for order <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong>
    has just been completed. Please find the signed pickup receipt attached as a PDF.</p>
@endif

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:14px 18px;font-size:13px;">
            <div><strong>Receipt number:</strong> <span style="font-family:'Courier New',monospace;">{{ $bon->bon_number }}</span></div>
            <div><strong>Date:</strong> {{ $bon->picked_up_at?->format('d-m-Y H:i') }}</div>
            @if ($isOphaal && $bon->weight_kg) <div><strong>Weight:</strong> {{ $bon->weight_kg }} kg</div> @endif
            @if ($isOphaal && $bon->seals->count())
                <div><strong>Number of seals:</strong> {{ $bon->seals->count() }}</div>
            @endif
            <div><strong>Driver:</strong> {{ $bon->driver_name_snapshot ?? '—' }} (licence ****{{ $bon->driver_license_last4 ?? '—' }})</div>
        </td>
    </tr>
</table>

@if ($isOphaal)
    <p style="font-size:13px;color:#555;">The seal numbers and the signed receipt are your proof that the material was collected sealed. Keep this email and PDF in your records — together with the <strong>Certificate of Destruction</strong> that follows, they form the complete audit trail.</p>
    <p>You will receive the certificate within 24 hours, once the material has been destroyed.</p>
@elseif ($isBezorg)
    <p>We collect periodically according to your subscription. You get a reminder the day before each pickup, and a certificate of destruction at every pickup.</p>
@else
    <p>Please keep this signed receipt in your records.</p>
@endif

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
