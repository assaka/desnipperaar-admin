@component('emails._layout', ['title' => 'Opdracht '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Uw opdracht is geannuleerd.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Uw opdracht <strong style="font-family:monospace;">{{ $order->order_number }}</strong> is geannuleerd. Wij komen dus niet langs en u ontvangt hiervoor geen factuur.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Opdracht</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;font-family:monospace;">{{ $order->order_number }}</td></tr>
    @if ($order->pickup_date)
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Vervallen ophaalmoment</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">
            {{ $order->pickup_date->format('d-m-Y') }}{{ $order->pickup_window ? ' ('.$order->pickup_window.')' : '' }}
        </td></tr>
    @endif
    @if ($reason)
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Reden</td>
        <td style="padding:8px 0;text-align:right;border-top:1px solid #EEE;">{{ $reason }}</td></tr>
    @endif
</table>

@if ($order->pickup_date)
<p>U hoeft op die dag niets klaar te zetten.</p>
@endif

<p>Klopt dit niet, of wilt u een nieuwe afspraak inplannen? Beantwoord dan gewoon deze e-mail. <strong>Houd het onderwerp ongewijzigd</strong>, dan komt uw bericht automatisch bij deze opdracht terecht.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
