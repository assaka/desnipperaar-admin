@component('emails._layout', ['title' => 'Bevestiging '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Bedankt — wij hebben uw aanvraag ontvangen.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Uw ordernummer is <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
We nemen binnen één werkdag contact met u op om de ophaling in te plannen.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Leveringsmethode</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;">{{ ucfirst($order->delivery_mode) }}service</td></tr>
    @if ($order->box_count)
        <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Aantal dozen</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">{{ $order->box_count }}</td></tr>
    @endif
    @if ($order->container_count)
        <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Rolcontainers</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">{{ $order->container_count }}</td></tr>
    @endif
    @if ($order->customer_postcode)
        <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Postcode</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">{{ $order->customer_postcode }}
                @if ($order->pilot) <span style="background:#F5C518;padding:1px 6px;font-size:11px;">Noord-pilot &middot; 20% korting</span> @endif
            </td></tr>
    @endif
</table>

<p>Heeft u vragen? Bel <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a> of mail op
<a href="mailto:hamid@desnipperaar.nl" style="color:#0A0A0A;">hamid@desnipperaar.nl</a>.</p>

<p>Met vriendelijke groet,<br>{{ $sender?->name ?? 'Hamid' }} — DeSnipperaar</p>
@endcomponent
