@php
    $days = [1 => 'lundi', 2 => 'mardi', 3 => 'mercredi', 4 => 'jeudi', 5 => 'vendredi'];
    $newDay = $order->sub_freq === '2pw' ? 'lundi et jeudi' : ($days[$order->subPickupWeekday()] ?? null);
    $freqLabels = ['4w' => '1x toutes les 4 semaines', '2w' => '1x toutes les 2 semaines', '1w' => '1x par semaine', '2pw' => '2x par semaine'];
@endphp
@component('emails.fr._layout', ['title' => "Jour d'enlèvement modifié ".$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Votre jour d'enlèvement fixe a changé.</h1>

<p>Bonjour {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Nous venons désormais chercher votre conteneur roulant scellé un autre jour fixe.
Votre fréquence, votre prix et votre durée restent inchangés.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Avant</td><td style="color:#666;text-decoration:line-through;">{{ ucfirst($previous) }}</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Désormais</td><td><strong>{{ ucfirst($newDay) }}</strong></td></tr>
    @if ($nextPickup)
        <tr><td style="background:#F5F5F5;font-weight:700;">Prochain enlèvement</td><td><strong>{{ $nextPickup->format('d-m-Y') }}</strong></td></tr>
    @endif
    <tr><td style="background:#F5F5F5;font-weight:700;">Fréquence</td><td>{{ $freqLabels[$order->sub_freq] ?? $order->sub_freq }}</td></tr>
</table>

<p>Placez le conteneur avant 08:00 ce jour-là, à l'endroit habituel. Nous vous envoyons un
rappel la veille.</p>

<p>Ce jour ne vous convient pas ? Répondez à cet e-mail et nous chercherons un autre jour.</p>

<p>Cordialement,<br>Team DeSnipperaar</p>
@endcomponent
