@component('emails._layout', ['title' => 'Zou je een review willen achterlaten?'])
@php
    $voornaam = trim(explode(' ', (string) $order->customer_name)[0]);
    $afzender = $sender?->name ? explode(' ', $sender->name)[0] : 'Hamid';
@endphp
<p>Hi {{ $voornaam }},</p>

<p>Nogmaals bedankt voor het vertrouwen en hopelijk is alles naar wens verlopen.<br>
Zou het erg waarderen als je een review zou kunnen plaatsen. Je zou me hier erg mee helpen.</p>

<p style="margin:20px 0;">
    <a href="{{ $reviewUrl }}" style="color:#0A0A0A;font-weight:bold;">{{ $reviewUrl }}</a>
</p>

<p>Alvast bedankt en nog een fijne dag!</p>

<p>Vriendelijke groet,<br>{{ $afzender }}</p>
@endcomponent
