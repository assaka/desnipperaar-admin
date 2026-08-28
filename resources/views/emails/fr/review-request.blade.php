@component('emails.fr._layout', ['title' => 'Voulez-vous nous laisser un avis ?'])
@php
    $prenom = trim(explode(' ', (string) $order->customer_name)[0]);
    $expediteur = $sender?->name ? explode(' ', $sender->name)[0] : 'Hamid';
@endphp
<p>Bonjour {{ $prenom }},</p>

<p>Merci encore pour votre confiance et j'espère que tout s'est passé comme vous le souhaitiez.<br>
J'apprécierais beaucoup que vous puissiez laisser un avis. Cela m'aiderait énormément.</p>

<p style="margin:20px 0;">
    <a href="{{ $reviewUrl }}" style="color:#0A0A0A;font-weight:bold;">{{ $reviewUrl }}</a>
</p>

<p>Merci d'avance et bonne journée!</p>

<p>Cordialement,<br>{{ $expediteur }}</p>
@endcomponent
