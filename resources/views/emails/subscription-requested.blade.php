@component('emails._layout', ['title' => 'Abonnementsaanvraag '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Bedankt! Uw abonnementsaanvraag is ontvangen.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Uw referentie is <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
Wij bevestigen uw abonnement en het ophaalschema binnen één werkdag.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Container</td><td>240 L verzegelde rolcontainer</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Frequentie</td><td>{{ $order->subFreqLabel() }}</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Looptijd</td><td>{{ $order->subTermLabel() }}</td></tr>
    @if ($order->sub_price_excl_btw)
        <tr><td style="background:#F5F5F5;font-weight:700;">Prijs</td><td>
            € {{ number_format($order->sub_price_excl_btw, 2, ',', '.') }}
            {{ $order->sub_term === 'jaar' ? 'per jaar' : 'per 4 weken' }} (excl. btw)
        </td></tr>
    @endif
</table>

<p>U hoeft nog niets te betalen. Het abonnement gaat pas lopen zodra u onze bevestiging heeft goedgekeurd.</p>

<p>Heeft u vragen? Reply dan op deze email.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
