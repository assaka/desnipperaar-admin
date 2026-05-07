@component('emails._layout', ['title' => 'Offerte-aanvraag '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Bedankt! Uw offerte-aanvraag is ontvangen.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Uw referentie is <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
Wij nemen binnen één werkdag contact met u op met een offerte op maat.</p>

<p>U hoeft nog niets te betalen of te bevestigen. Een offerte is vrijblijvend tot u akkoord gaat.</p>

<p>Heeft u vragen? Reply dan op deze email.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
