@component('emails._layout', ['title' => 'Abonnement opgezegd '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Uw abonnement is opgezegd.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Wij hebben uw opzegging verwerkt. Uw abonnement <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong> loopt door tot en met <strong>{{ $endsOn->format('d-m-Y') }}</strong>.</p>

<p>Tot die datum halen wij op volgens het gebruikelijke schema en wordt het abonnement normaal gefactureerd. Daarna stopt de facturatie.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Loopt tot en met</td><td><strong>{{ $endsOn->format('d-m-Y') }}</strong></td></tr>
    @if ($lastPickup)
        <tr><td style="background:#F5F5F5;font-weight:700;">Laatste ophaling</td><td>{{ $lastPickup->pickup_date->format('d-m-Y') }}</td></tr>
    @endif
    @if ($returnCost)
        <tr><td style="background:#F5F5F5;font-weight:700;">Retourkosten</td><td>€ {{ number_format($returnCost, 2, ',', '.') }}</td></tr>
    @endif
</table>

@if ($returnCost)
<p style="background:#FFF8E1;border-left:4px solid #F5C518;padding:12px 14px;">
    Omdat u Flex binnen twaalf maanden opzegt komen er eenmalig € {{ number_format($returnCost, 2, ',', '.') }} logistieke retourkosten bij, exclusief btw. Dat zijn de werkelijke kosten van de retourrit, geen boete. Het bedrag komt op uw laatste factuur.
</p>
@endif

<p>Wij halen de rolcontainer op of na de einddatum bij u weg. U hoeft daar verder niets voor te doen.</p>

<p>Wilt u later weer starten? Laat het weten, dan zetten wij het zo weer voor u klaar.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
