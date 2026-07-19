@php
    $freqLabels = ['4w' => '1x toutes les 4 semaines', '2w' => '1x toutes les 2 semaines', '1w' => '1x par semaine', '2pw' => '2x par semaine'];
    $termLabels = ['flex' => 'Flex (min. 3 mois, puis mensuel)', 'vast' => 'Fixe (12 mois)', 'jaar' => 'Paiement annuel (12 mois d\'avance)'];
    $per = $order->sub_term === 'jaar' ? 'par an' : 'par mois';
    $days = [1 => 'lundi', 2 => 'mardi', 3 => 'mercredi', 4 => 'jeudi', 5 => 'vendredi'];
    $pickupDay = $order->sub_freq === '2pw'
        ? 'lundi et jeudi'
        : ($days[$order->subPickupWeekday()] ?? null);
    $next = $order->nextPickupDate();
@endphp
@component('emails.fr._layout', ['title' => 'Abonnement '.$order->order_number.' actif'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Votre abonnement est actif.</h1>

<p>Bonjour {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Merci pour votre accord. Votre abonnement est enregistré sous la référence
<strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Conteneur</td><td>Conteneur roulant scellé 240 L</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Fréquence</td><td>{{ $freqLabels[$order->sub_freq] ?? $order->sub_freq }}</td></tr>
    @if ($pickupDay)
        <tr><td style="background:#F5F5F5;font-weight:700;">Jour d'enlèvement fixe</td><td>{{ ucfirst($pickupDay) }}</td></tr>
    @endif
    <tr><td style="background:#F5F5F5;font-weight:700;">Livraison du conteneur</td><td><strong>{{ $order->sub_active_from->format('d-m-Y') }}</strong></td></tr>
    @if ($next)
        <tr><td style="background:#F5F5F5;font-weight:700;">Premier enlèvement</td><td><strong>{{ $next->format('d-m-Y') }}</strong></td></tr>
    @endif
    <tr><td style="background:#F5F5F5;font-weight:700;">Durée</td><td>{{ $termLabels[$order->sub_term] ?? $order->sub_term }}</td></tr>
    @if ($order->sub_price_excl_btw)
        <tr><td style="background:#F5F5F5;font-weight:700;">Prix</td><td>
            <strong>€ {{ number_format($order->sub_price_excl_btw, 2, ',', '.') }}</strong> {{ $per }} hors TVA<br>
            <span style="color:#666;">€ {{ number_format($order->sub_price_excl_btw * 1.21, 2, ',', '.') }} {{ $per }} TVA 21% incluse</span>
        </td></tr>
    @endif
</table>

<p>Nous livrons le conteneur le {{ $order->sub_active_from->format('d-m-Y') }}. Votre abonnement
démarre ce jour-là. Vous avez ensuite le temps de le remplir.
@if ($next)
Le premier enlèvement n'a lieu que le {{ $next->format('d-m-Y') }}.
@endif
Ensuite, nous collectons selon ce calendrier, sans autre démarche de votre part. Si un jour
d'enlèvement tombe un jour férié, nous venons le jour ouvré suivant et le reste du calendrier
reste inchangé.</p>

<p>Vous recevez un certificat de destruction selon DIN 66399 à chaque enlèvement.</p>

<p>Des questions ou une modification du calendrier ? Répondez simplement à cet e-mail.</p>

<p>Cordialement,<br>Team DeSnipperaar</p>
@endcomponent
