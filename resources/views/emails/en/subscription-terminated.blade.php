@component('emails.en._layout', ['title' => 'Subscription cancelled '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Your subscription has been cancelled.</h1>

<p>Dear {{ explode(' ', $order->customer_name)[0] }},</p>

<p>We have processed your cancellation. Your subscription <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong> runs until and including <strong>{{ $endsOn->format('d-m-Y') }}</strong>.</p>

<p>Until that date we collect on the usual schedule and the subscription is invoiced as normal. After that, invoicing stops.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Runs until and including</td><td><strong>{{ $endsOn->format('d-m-Y') }}</strong></td></tr>
    @if ($lastPickup)
        <tr><td style="background:#F5F5F5;font-weight:700;">Last pickup</td><td>{{ $lastPickup->pickup_date->format('d-m-Y') }}</td></tr>
    @endif
    @if ($returnCost)
        <tr><td style="background:#F5F5F5;font-weight:700;">Return cost</td><td>€ {{ number_format($returnCost, 2, ',', '.') }}</td></tr>
    @endif
</table>

@if ($returnCost)
<p style="background:#FFF8E1;border-left:4px solid #F5C518;padding:12px 14px;">
    Because you are cancelling Flex within twelve months, a one-off € {{ number_format($returnCost, 2, ',', '.') }} logistics return cost applies, excl. VAT. That is the actual cost of the return trip, not a penalty. It appears on your final invoice.
</p>
@endif

<p>We collect the roll container on or after the end date. Nothing further is needed from you.</p>

<p>Want to start again later? Let us know and we will set it up for you.</p>

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
