@component('emails.fr._layout', ['title' => 'Livraison de votre conteneur '.$visit->bon_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Nous livrons votre conteneur demain.</h1>

<p>Bonjour {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Un petit rappel. Demain, {{ $visit->planned_for->format('d-m-Y') }}, nous livrons votre
conteneur roulant scellé de 240 litres.</p>

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Jour de livraison</td><td><strong>{{ $visit->planned_for->format('d-m-Y') }}</strong></td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Adresse</td><td>{{ $order->customer_address }}, {{ $order->customer_postcode }} {{ $order->customer_city }}</td></tr>
    @if ($firstPickup)
        <tr><td style="background:#F5F5F5;font-weight:700;">Premier enlèvement</td><td><strong>{{ $firstPickup->format('d-m-Y') }}</strong></td></tr>
    @endif
    <tr><td style="background:#F5F5F5;font-weight:700;">Référence</td><td style="font-family:monospace;">{{ $visit->bon_number }}</td></tr>
</table>

<p>Veillez à ce que quelqu'un soit présent ou que l'emplacement soit accessible, afin que nous
puissions placer le conteneur au bon endroit. Rien d'autre à préparer.</p>

@if ($firstPickup)
    <p>Vous pouvez le remplir dès demain. Nous venons le vider pour la première fois le
    {{ $firstPickup->format('d-m-Y') }}, et nous vous envoyons un rappel la veille.</p>
@endif

<p>Vous recevez un certificat de destruction selon DIN 66399 à chaque enlèvement.</p>

<p>Demain ne vous convient pas ? Répondez à cet e-mail et nous trouverons un autre moment.</p>

<p>Cordialement,<br>Team DeSnipperaar</p>
@endcomponent
