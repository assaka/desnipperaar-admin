@php
    // Drie toestanden. 'confirm' vraagt eerst, want een mailscanner die de link
    // volgt mag niets afvinken.
    $copy = [
        'nl' => [
            'confirm' => ['t' => 'Ophaalmail ontvangen?', 'm' => 'Bevestig hieronder dat u onze e-mail met het ophaalmoment heeft ontvangen.'],
            'done'    => ['t' => 'Bedankt', 'm' => 'Fijn, dan zien wij u op het afgesproken moment.'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'Deze link is niet (meer) geldig. Vragen? Stuur ons een WhatsApp of bel 06-10229965.'],
            'moment' => 'Ophaalmoment', 'knop' => 'Ontvangen', 'site' => 'Naar desnipperaar.nl',
            'gewijzigd' => 'Let op, uw ophaalmoment is intussen gewijzigd. Dit is het actuele moment.',
            'nietUit' => 'Komt dit moment niet uit?', 'wa' => 'Stuur ons een WhatsApp', 'waTekst' => 'Ophaalmoment :nr komt niet uit. ',
        ],
        'en' => [
            'confirm' => ['t' => 'Pickup e-mail received?', 'm' => 'Please confirm below that you received our e-mail with the pickup date.'],
            'done'    => ['t' => 'Thank you', 'm' => 'Great, see you at the agreed time.'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'This link is not (or no longer) valid. Questions? Send us a WhatsApp or call +31 6 10229965.'],
            'moment' => 'Pickup', 'knop' => 'Received', 'site' => 'Go to desnipperaar.nl',
            'gewijzigd' => 'Please note, your pickup has been rescheduled since. This is the current slot.',
            'nietUit' => 'Doesn\'t this slot suit you?', 'wa' => 'Send us a WhatsApp', 'waTekst' => 'Pickup :nr does not suit me. ',
        ],
        'fr' => [
            'confirm' => ['t' => 'E-mail d\'enlèvement reçu ?', 'm' => 'Confirmez ci-dessous que vous avez bien reçu notre e-mail avec la date d\'enlèvement.'],
            'done'    => ['t' => 'Merci', 'm' => 'Parfait, à bientôt au moment convenu.'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'Ce lien n\'est pas (ou plus) valide. Des questions ? Envoyez-nous un WhatsApp ou appelez le +31 6 10229965.'],
            'moment' => 'Enlèvement', 'knop' => 'Bien reçu', 'site' => 'Aller sur desnipperaar.nl',
            'gewijzigd' => 'Attention, votre enlèvement a été modifié entre-temps. Voici le créneau actuel.',
            'nietUit' => 'Ce créneau ne vous convient pas ?', 'wa' => 'Envoyez-nous un WhatsApp', 'waTekst' => 'L\'enlèvement :nr ne me convient pas. ',
        ],
        'es' => [
            'confirm' => ['t' => '¿Ha recibido el correo de recogida?', 'm' => 'Confirme abajo que ha recibido nuestro correo con la fecha de recogida.'],
            'done'    => ['t' => 'Gracias', 'm' => 'Perfecto, hasta el momento acordado.'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'Este enlace no es (o ya no es) válido. ¿Preguntas? Envíenos un WhatsApp o llame al +31 6 10229965.'],
            'moment' => 'Recogida', 'knop' => 'Recibido', 'site' => 'Ir a desnipperaar.nl',
            'gewijzigd' => 'Atención, su recogida ha cambiado mientras tanto. Este es el momento actual.',
            'nietUit' => '¿No le viene bien este momento?', 'wa' => 'Envíenos un WhatsApp', 'waTekst' => 'La recogida :nr no me viene bien. ',
        ],
    ];
    $all = $copy[$lang] ?? $copy['nl'];
    $c = $all[$state];
    $toonMoment = $state !== 'invalid' && $order?->pickup_date;
    $waUrl = $order ? 'https://wa.me/31610229965?text='.rawurlencode(str_replace(':nr', $order->order_number, $all['waTekst'])) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $c['t'] }}</title>
    <style>
        body{margin:0;background:#EEECE4;font-family:Arial,Helvetica,sans-serif;color:#0A0A0A;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:20px;}
        .card{background:#fff;max-width:480px;width:100%;border-top:6px solid #F5C518;padding:36px 32px;box-shadow:0 10px 40px rgba(0,0,0,.12);text-align:center;box-sizing:border-box;}
        h1{font-size:26px;font-weight:900;margin:0 0 14px;}
        p{font-size:15px;line-height:1.6;color:#333;margin:0 0 24px;}
        .moment{background:#F7F7F4;border-left:4px solid #F5C518;text-align:left;padding:12px 16px;margin:0 0 24px;font-size:15px;}
        .moment small{display:block;font-family:'Courier New',monospace;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#555;margin-bottom:4px;}
        .let-op{background:#FFF8E1;border-left:4px solid #E0A800;text-align:left;padding:10px 14px;margin:0 0 16px;font-size:14px;font-weight:700;}
        .btn{display:inline-block;background:#0A0A0A;color:#F5C518;font-weight:700;text-decoration:none;padding:13px 26px;border:0;font-size:15px;font-family:inherit;cursor:pointer;}
        .wa{margin:22px 0 0;font-size:14px;color:#555;}
        .wa a{color:#0A0A0A;font-weight:700;}
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $c['t'] }}</h1>
        <p>{{ $c['m'] }}</p>
        @if ($gewijzigd)
            <div class="let-op">{{ $all['gewijzigd'] }}</div>
        @endif
        @if ($toonMoment)
            <div class="moment">
                <small>{{ $all['moment'] }} · {{ $order->order_number }}</small>
                <strong>{{ $order->pickup_date->locale($lang)->translatedFormat('l d F Y') }}</strong>
                @if ($order->pickup_window) · {{ str_replace('-', ' – ', $order->pickup_window) }} @endif
            </div>
        @endif
        @if ($state === 'confirm')
            {{-- Host-relatief, want de pagina komt via desnipperaar.nl binnen. --}}
            <form method="post" action="/ontvangen/{{ rawurlencode($token) }}">
                <input type="hidden" name="lang" value="{{ $lang }}">
                <input type="hidden" name="m" value="{{ $m }}">
                <button class="btn" type="submit">{{ $all['knop'] }}</button>
            </form>
        @else
            <a class="btn" href="https://desnipperaar.nl">{{ $all['site'] }}</a>
        @endif
        @if ($toonMoment)
            <p class="wa">{{ $all['nietUit'] }} <a href="{{ $waUrl }}">{{ $all['wa'] }}</a></p>
        @endif
    </div>
</body>
</html>
