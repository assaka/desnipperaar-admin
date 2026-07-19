@component('emails.fr._layout', ['title' => "Rappel d'enlèvement ".$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Nous passons demain.</h1>

<p>Bonjour {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Un petit rappel. Demain, {{ $order->pickup_date->format('d-m-Y') }}, nous venons enlever votre conteneur roulant scellé.</p>

@if ($shifted)
<p style="background:#FFF8E1;border-left:4px solid #F5C518;padding:12px 14px;">
    À noter. Nous venons normalement le {{ $order->subscription_scheduled_for->format('d-m-Y') }}, mais ce jour est cette fois un jour férié ou un week-end. Nous venons donc le {{ $order->pickup_date->format('d-m-Y') }}. Votre jour d'enlèvement fixe ne change pas.
</p>
@endif

<table cellpadding="6" style="border-collapse:collapse;font-size:14px;margin:16px 0;">
    <tr><td style="background:#F5F5F5;font-weight:700;">Date d'enlèvement</td><td><strong>{{ $order->pickup_date->format('d-m-Y') }}</strong></td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Adresse</td><td>{{ $order->customer_address }}, {{ $order->customer_postcode }} {{ $order->customer_city }}</td></tr>
    <tr><td style="background:#F5F5F5;font-weight:700;">Référence</td><td style="font-family:monospace;">{{ $order->order_number }}</td></tr>
</table>

<p>Placez le conteneur à l'endroit habituel avant 08:00, pour que notre chauffeur puisse y accéder.</p>

<p>Après la destruction, vous recevez votre certificat de destruction selon DIN 66399.</p>

<p>Demain ne vous convient pas ? Répondez à cet e-mail et nous trouverons un autre moment.</p>

<p>Cordialement,<br>Team DeSnipperaar</p>
@endcomponent
