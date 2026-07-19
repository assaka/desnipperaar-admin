@component('emails._layout', ['title' => 'Termijn abonnement '.$order->order_number.' loopt af'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Uw termijn loopt af op {{ $renewalDate->format('d-m-Y') }}.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Uw abonnement <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>
loopt over ongeveer een maand uit zijn huidige termijn. Uw container blijft gewoon staan
en wij blijven ophalen. Alleen de voorwaarden veranderen.</p>

<p style="background:#F5F5F5;border-left:4px solid #F5C518;padding:12px 14px;">
    <strong>Doet u niets?</strong> Dan gaat uw abonnement vanaf {{ $renewalDate->copy()->addDay()->format('d-m-Y') }}
    door voor
    @if ($monthlyPrice)
        € {{ number_format($monthlyPrice, 2, ',', '.') }} per 4 weken excl. btw, het voordeeltarief,
    @endif
    U kunt op elk moment stoppen. U zit dan nergens meer aan vast.
</p>

<p>Wilt u iets anders? Antwoord dan op deze email, dan regelen wij het.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    @if ($yearlyPrice)
        <tr>
            <td style="background:#F5F5F5;font-weight:700;vertical-align:top;">Nog een jaar vooruit</td>
            <td>€ {{ number_format($yearlyPrice, 2, ',', '.') }} per jaar excl. btw. De voordeligste optie, u betaalt twaalf maanden in één keer.</td>
        </tr>
    @endif
    <tr>
        <td style="background:#F5F5F5;font-weight:700;vertical-align:top;">Maandelijks (voordeeltarief)</td>
        <td>
            @if ($monthlyPrice)
                € {{ number_format($monthlyPrice, 2, ',', '.') }} per 4 weken excl. btw.
            @endif
            Altijd opzegbaar. Dit gebeurt automatisch als u niet reageert.
        </td>
    </tr>
    <tr>
        <td style="background:#F5F5F5;font-weight:700;vertical-align:top;">Stoppen</td>
        <td>Laat het weten, dan halen wij de container op na {{ $renewalDate->format('d-m-Y') }}. Geen retourkosten.</td>
    </tr>
</table>

<p>Huidige frequentie: {{ $order->subFreqLabel() }}. Wilt u vaker of minder vaak laten ophalen?
Dat kan bij deze overgang zonder kosten.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
