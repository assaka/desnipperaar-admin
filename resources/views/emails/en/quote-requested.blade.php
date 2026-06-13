@component('emails.en._layout', ['title' => 'Quote request '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Thank you! Your quote request has been received.</h1>

<p>Dear {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Your reference is <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
We will contact you within one business day with a tailored quote.</p>

<p>You do not need to pay or confirm anything yet. A quote is non-binding until you accept it.</p>

<p>Any questions? Just reply to this email.</p>

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
