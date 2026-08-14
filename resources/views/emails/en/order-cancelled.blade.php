@component('emails._layout', ['title' => 'Order '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Your order has been cancelled.</h1>

<p>Dear {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Your order <strong style="font-family:monospace;">{{ $order->order_number }}</strong> has been cancelled. We will not be coming by and you will not receive an invoice for it.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Order</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;font-family:monospace;">{{ $order->order_number }}</td></tr>
    @if ($order->pickup_date)
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Cancelled pickup slot</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">
            {{ $order->pickup_date->format('d-m-Y') }}{{ $order->pickup_window ? ' ('.$order->pickup_window.')' : '' }}
        </td></tr>
    @endif
    @if ($reason)
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Reason</td>
        <td style="padding:8px 0;text-align:right;border-top:1px solid #EEE;">{{ $reason }}</td></tr>
    @endif
</table>

@if ($order->pickup_date)
<p>There is nothing you need to put out that day.</p>
@endif

<p>Is this not right, or would you like to book a new slot? Just reply to this email. <strong>Keep the subject unchanged</strong> so your message is added to this order automatically.</p>

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
