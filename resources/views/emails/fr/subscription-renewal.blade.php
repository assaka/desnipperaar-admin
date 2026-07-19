@php
    $freqLabels = ['4w' => '1x toutes les 4 semaines', '2w' => '1x toutes les 2 semaines', '1w' => '1x par semaine', '2pw' => '2x par semaine'];
@endphp
@component('emails.fr._layout', ['title' => 'Abonnement '.$order->order_number.' arrive à échéance'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Votre période se termine le {{ $renewalDate->format('d-m-Y') }}.</h1>

<p>Bonjour {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Votre abonnement <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>
arrive au terme de sa période actuelle dans environ un mois. Votre conteneur reste en place et
nous continuons les enlèvements. C'est vous qui choisissez la suite.</p>

<p style="background:#F5F5F5;border-left:4px solid #F5C518;padding:12px 14px;">
    <strong>Vous ne faites rien ?</strong> À partir du {{ $renewalDate->copy()->addDay()->format('d-m-Y') }},
    votre abonnement passe en <strong>Flex</strong>@if ($flexPrice), € {{ number_format($flexPrice, 2, ',', '.') }} toutes les 4 semaines hors TVA@endif.
    Flex est résiliable à tout moment, sans engagement. Si vous renouvelez, vous conservez le tarif avantageux.
</p>

<p><strong>Vous préférez autre chose ?</strong> Répondez à cet e-mail avec l'un de ces choix :</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    @if ($yearlyPrice)
        <tr>
            <td style="background:#F5F5F5;font-weight:700;vertical-align:top;">Renouveler — encore un an d'avance</td>
            <td>€ {{ number_format($yearlyPrice, 2, ',', '.') }} par an hors TVA. L'option la plus avantageuse, douze mois en une fois.</td>
        </tr>
    @endif
    @if ($vastPrice)
        <tr>
            <td style="background:#F5F5F5;font-weight:700;vertical-align:top;">Renouveler — encore une période fixe</td>
            <td>€ {{ number_format($vastPrice, 2, ',', '.') }} toutes les 4 semaines hors TVA, douze mois. Le tarif avantageux.</td>
        </tr>
    @endif
    <tr>
        <td style="background:#F5F5F5;font-weight:700;vertical-align:top;">Passer en Flex</td>
        <td>
            @if ($flexPrice) € {{ number_format($flexPrice, 2, ',', '.') }} toutes les 4 semaines hors TVA. @endif
            Résiliable à tout moment. C'est ce qui se passe automatiquement sans réponse de votre part.
        </td>
    </tr>
    <tr>
        <td style="background:#F5F5F5;font-weight:700;vertical-align:top;">Arrêter</td>
        <td>Faites-le nous savoir et nous récupérons le conteneur après le {{ $renewalDate->format('d-m-Y') }}. Sans frais de retour.</td>
    </tr>
</table>

<p>Fréquence actuelle : {{ $freqLabels[$order->sub_freq] ?? $order->sub_freq }}. Souhaitez-vous plus ou moins
d'enlèvements ? Vous pouvez le modifier gratuitement lors de cette transition.</p>

<p>Cordialement,<br>Team DeSnipperaar</p>
@endcomponent
