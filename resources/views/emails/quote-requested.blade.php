@component('emails._layout', ['title' => 'Offerte-aanvraag '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Bedankt — uw offerte-aanvraag is ontvangen.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Uw referentie is <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
Wij nemen binnen één werkdag contact met u op met een offerte op maat.</p>

<p>U hoeft nog niets te betalen of te bevestigen — een offerte is vrijblijvend tot u akkoord gaat.</p>

<p>Heeft u vragen? Bel <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a>.</p>

<p>Met vriendelijke groet,<br>{{ $sender?->name ?? 'Hamid' }} — DeSnipperaar</p>
@endcomponent
