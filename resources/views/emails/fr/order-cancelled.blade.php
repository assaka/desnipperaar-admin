@component('emails._layout', ['title' => 'Commande '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Votre commande est annulée.</h1>

<p>Bonjour {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Votre commande <strong style="font-family:monospace;">{{ $order->order_number }}</strong> est annulée. Nous ne passerons donc pas et vous ne recevrez aucune facture.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Commande</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;font-family:monospace;">{{ $order->order_number }}</td></tr>
    @if ($order->pickup_date)
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Créneau annulé</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">
            {{ $order->pickup_date->format('d-m-Y') }}{{ $order->pickup_window ? ' ('.$order->pickup_window.')' : '' }}
        </td></tr>
    @endif
    @if ($reason)
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Motif</td>
        <td style="padding:8px 0;text-align:right;border-top:1px solid #EEE;">{{ $reason }}</td></tr>
    @endif
</table>

@if ($order->pickup_date)
<p>Vous n'avez rien à préparer ce jour-là.</p>
@endif

<p>Ce n'est pas correct, ou vous souhaitez un nouveau rendez-vous ? Répondez simplement à cet e-mail. <strong>Conservez l'objet tel quel</strong>, votre message sera ajouté automatiquement à cette commande.</p>

<p>Cordialement,<br>L'équipe DeSnipperaar</p>
@endcomponent
