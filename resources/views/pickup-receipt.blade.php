@php
    // Drie toestanden. 'confirm' vraagt eerst, want een mailscanner die de link
    // volgt mag niets afvinken.
    $copy = [
        'nl' => [
            'confirm' => ['t' => 'Schikt dit ophaalmoment?', 'm' => 'Laat ons even weten of dit schikt.'],
            'done'    => ['t' => 'Bedankt', 'm' => 'Fijn, dan zien wij u op het afgesproken moment.'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'Deze link is niet (meer) geldig. Vragen? Stuur ons een WhatsApp of bel 06-10229965.'],
            'moment' => 'Ophaalmoment', 'knop' => 'Ja, dit schikt', 'site' => 'Naar desnipperaar.nl',
            'gewijzigd' => 'Let op, uw ophaalmoment is intussen gewijzigd. Dit is het actuele moment.',
            'nietUit' => 'Mocht het niet uitkomen, neem dan even contact met ons op via', 'wa' => 'WhatsApp', 'bel' => 'of 06-10229965.', 'nietUitDone' => 'Komt het toch niet uit? Neem dan binnen 24 uur contact met ons op via', 'waTekst' => 'Ophaalmoment :nr komt niet uit. ',
        ],
        'en' => [
            'confirm' => ['t' => 'Does this pickup slot suit you?', 'm' => 'Please let us know whether this suits you.'],
            'done'    => ['t' => 'Thank you', 'm' => 'Great, see you at the agreed time.'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'This link is not (or no longer) valid. Questions? Send us a WhatsApp or call +31 6 10229965.'],
            'moment' => 'Pickup', 'knop' => 'Yes, this suits me', 'site' => 'Go to desnipperaar.nl',
            'gewijzigd' => 'Please note, your pickup has been rescheduled since. This is the current slot.',
            'nietUit' => 'If it doesn\'t suit you, please get in touch via', 'wa' => 'WhatsApp', 'bel' => 'or +31 6 10229965.', 'nietUitDone' => 'Does it not suit you after all? Then please contact us within 24 hours via', 'waTekst' => 'Pickup :nr does not suit me. ',
        ],
        'fr' => [
            'confirm' => ['t' => 'Ce créneau vous convient-il ?', 'm' => 'Dites-nous si ce créneau vous convient.'],
            'done'    => ['t' => 'Merci', 'm' => 'Parfait, à bientôt au moment convenu.'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'Ce lien n\'est pas (ou plus) valide. Des questions ? Envoyez-nous un WhatsApp ou appelez le +31 6 10229965.'],
            'moment' => 'Enlèvement', 'knop' => 'Oui, ça me convient', 'site' => 'Aller sur desnipperaar.nl',
            'gewijzigd' => 'Attention, votre enlèvement a été modifié entre-temps. Voici le créneau actuel.',
            'nietUit' => 'Si ce n\'est pas possible, contactez-nous via', 'wa' => 'WhatsApp', 'bel' => 'ou au +31 6 10229965.', 'nietUitDone' => 'Finalement, cela ne vous convient pas ? Contactez-nous dans les 24 heures via', 'waTekst' => 'L\'enlèvement :nr ne me convient pas. ',
        ],
        'es' => [
            'confirm' => ['t' => '¿Le viene bien este momento?', 'm' => 'Indíquenos si le viene bien.'],
            'done'    => ['t' => 'Gracias', 'm' => 'Perfecto, hasta el momento acordado.'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'Este enlace no es (o ya no es) válido. ¿Preguntas? Envíenos un WhatsApp o llame al +31 6 10229965.'],
            'moment' => 'Recogida', 'knop' => 'Sí, me viene bien', 'site' => 'Ir a desnipperaar.nl',
            'gewijzigd' => 'Atención, su recogida ha cambiado mientras tanto. Este es el momento actual.',
            'nietUit' => 'Si no le viene bien, contáctenos por', 'wa' => 'WhatsApp', 'bel' => 'o al +31 6 10229965.', 'nietUitDone' => '¿Al final no le viene bien? Contáctenos en un plazo de 24 horas por', 'waTekst' => 'La recogida :nr no me viene bien. ',
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
            <p class="wa">{{ $state === 'done' ? $all['nietUitDone'] : $all['nietUit'] }} <a href="{{ $waUrl }}">{{ $all['wa'] }}</a> {{ $all['bel'] }}</p>
        @endif
    </div>
</body>
</html>
