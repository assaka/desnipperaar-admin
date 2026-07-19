@component('emails.en._layout', ['title' => 'Pickup reminder '.$visit->bon_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">We collect tomorrow.</h1>

<p>Dear {{ explode(' ', $order->customer_name)[0] }},</p>

<p>A short reminder. Tomorrow, {{ $visit->planned_for->format('d-m-Y') }}, we collect your sealed roll container.</p>

@if ($shifted)
<p style="background:#FFF8E1;border-left:4px solid #F5C518;padding:12px 14px;">
    Please note. We normally come on {{ $visit->scheduled_for->format('d-m-Y') }}, but that day is a public holiday or weekend this time. So we come on {{ $visit->planned_for->format('d-m-Y') }} instead. Your fixed pickup day does not change.
</p>
@endif

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Pickup date</td><td><strong>{{ $visit->planned_for->format('d-m-Y') }}</strong></td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Address</td><td>{{ $order->customer_address }}, {{ $order->customer_postcode }} {{ $order->customer_city }}</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Reference</td><td style="font-family:monospace;">{{ $visit->bon_number }}</td></tr>
</table>

<p>Please put the container in the usual spot before 08:00 so our driver can reach it.</p>

<p>After destruction you receive your certificate of destruction to DIN 66399.</p>

<p>Does tomorrow not suit you? Just reply to this email and we will find another moment.</p>

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
