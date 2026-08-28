@component('emails.es._layout', ['title' => '¿Nos deja una reseña?'])
@php
    $nombre = trim(explode(' ', (string) $order->customer_name)[0]);
    $remitente = $sender?->name ? explode(' ', $sender->name)[0] : 'Hamid';
@endphp
<p>Hola {{ $nombre }},</p>

<p>Gracias de nuevo por su confianza y espero que todo haya salido como usted quería.<br>
Le agradecería mucho que pudiera dejar una reseña. Me ayudaría enormemente.</p>

<p style="margin:20px 0;">
    <a href="{{ $reviewUrl }}" style="color:#0A0A0A;font-weight:bold;">{{ $reviewUrl }}</a>
</p>

<p>Gracias de antemano y que tenga un buen día.</p>

<p>Un saludo cordial,<br>{{ $remitente }}</p>
@endcomponent
