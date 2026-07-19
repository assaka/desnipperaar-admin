@component('emails.fr._layout', ['title' => 'Abonnement résilié '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Votre abonnement est résilié.</h1>

<p>Bonjour {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Nous avons traité votre résiliation. Votre abonnement <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong> court jusqu'au <strong>{{ $endsOn->format('d-m-Y') }}</strong> inclus.</p>

<p>Jusqu'à cette date, nous collectons selon le calendrier habituel et l'abonnement est facturé normalement. Ensuite, la facturation s'arrête.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Court jusqu'au</td><td><strong>{{ $endsOn->format('d-m-Y') }}</strong></td></tr>
    @if ($lastPickup)
        <tr><td style="background:#F5F5F5;font-weight:700;">Dernier enlèvement</td><td>{{ $lastPickup->pickup_date->format('d-m-Y') }}</td></tr>
    @endif
    @if ($returnCost)
        <tr><td style="background:#F5F5F5;font-weight:700;">Frais de retour</td><td>€ {{ number_format($returnCost, 2, ',', '.') }}</td></tr>
    @endif
</table>

@if ($returnCost)
<p style="background:#FFF8E1;border-left:4px solid #F5C518;padding:12px 14px;">
    Comme vous résiliez Flex avant douze mois, des frais logistiques de retour de € {{ number_format($returnCost, 2, ',', '.') }} hors TVA s'appliquent une seule fois. Il s'agit du coût réel du trajet retour, pas d'une pénalité. Le montant figure sur votre dernière facture.
</p>
@endif

<p>Nous récupérons le conteneur roulant à partir de la date de fin. Vous n'avez rien à faire.</p>

<p>Vous souhaitez reprendre plus tard ? Faites-le nous savoir et nous remettons tout en place.</p>

<p>Cordialement,<br>Team DeSnipperaar</p>
@endcomponent
