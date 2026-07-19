@php
    $freqLabels = ['4w' => '1x toutes les 4 semaines', '2w' => '1x toutes les 2 semaines', '1w' => '1x par semaine', '2pw' => '2x par semaine'];
    $termLabels = ['flex' => 'Flex (min. 3 mois, puis mensuel)', 'vast' => 'Fixe (12 mois)', 'jaar' => 'Paiement annuel (12 mois d\'avance)'];
@endphp
@component('emails.fr._layout', ['title' => 'Demande d\'abonnement '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Merci ! Votre demande d'abonnement a bien été reçue.</h1>

<p>Bonjour {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Votre référence est <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
Nous confirmons votre abonnement et votre calendrier d'enlèvement sous un jour ouvré.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Conteneur</td><td>Conteneur roulant scellé 240 L</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Fréquence</td><td>{{ $freqLabels[$order->sub_freq] ?? $order->sub_freq }}</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Durée</td><td>{{ $termLabels[$order->sub_term] ?? $order->sub_term }}</td></tr>
    @if ($order->sub_price_excl_btw)
        <tr><td style="background:#F5F5F5;font-weight:700;">Prix</td><td>
            € {{ number_format($order->sub_price_excl_btw, 2, ',', '.') }}
            {{ $order->sub_term === 'jaar' ? 'par an' : 'par mois' }} (hors TVA)
        </td></tr>
    @endif
</table>

<p>Vous n'avez encore rien à payer. L'abonnement ne démarre qu'une fois notre confirmation approuvée par vos soins.</p>

<p>Des questions ? Répondez simplement à cet e-mail.</p>

<p>Cordialement,<br>Team DeSnipperaar</p>
@endcomponent
