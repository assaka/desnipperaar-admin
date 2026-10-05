@php
    // Drie toestanden, net als de afmeldpagina. 'confirm' vraagt eerst, want een
    // mailscanner die de link volgt mag niets afvinken.
    $copy = [
        'nl' => [
            'confirm' => ['t' => 'Ophaalmoment ontvangen', 'm' => 'Bevestig hieronder dat u onze e-mail met het ophaalmoment heeft ontvangen.', 'b' => 'Ja, ontvangen'],
            'done'    => ['t' => 'Bedankt', 'm' => 'Wij hebben uw bevestiging ontvangen. Tot dan.', 'b' => 'Naar desnipperaar.nl'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'Deze link is niet (meer) geldig. Vragen? Bel 06-10229965.', 'b' => 'desnipperaar.nl'],
            'moment'  => 'Ophaalmoment',
        ],
        'en' => [
            'confirm' => ['t' => 'Pickup e-mail received', 'm' => 'Please confirm below that you received our e-mail with the pickup date.', 'b' => 'Yes, received'],
            'done'    => ['t' => 'Thank you', 'm' => 'We have received your confirmation. See you then.', 'b' => 'Go to desnipperaar.nl'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'This link is not (or no longer) valid. Questions? Call +31 6 10229965.', 'b' => 'desnipperaar.nl'],
            'moment'  => 'Pickup',
        ],
        'fr' => [
            'confirm' => ['t' => 'E-mail d\'enlèvement reçu', 'm' => 'Confirmez ci-dessous que vous avez bien reçu notre e-mail avec la date d\'enlèvement.', 'b' => 'Oui, bien reçu'],
            'done'    => ['t' => 'Merci', 'm' => 'Nous avons bien reçu votre confirmation. À bientôt.', 'b' => 'Aller sur desnipperaar.nl'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'Ce lien n\'est pas (ou plus) valide. Des questions ? Appelez le +31 6 10229965.', 'b' => 'desnipperaar.nl'],
            'moment'  => 'Enlèvement',
        ],
        'es' => [
            'confirm' => ['t' => 'Correo de recogida recibido', 'm' => 'Confirme abajo que ha recibido nuestro correo con la fecha de recogida.', 'b' => 'Sí, recibido'],
            'done'    => ['t' => 'Gracias', 'm' => 'Hemos recibido su confirmación. Hasta entonces.', 'b' => 'Ir a desnipperaar.nl'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'Este enlace no es (o ya no es) válido. ¿Preguntas? Llame al +31 6 10229965.', 'b' => 'desnipperaar.nl'],
            'moment'  => 'Recogida',
        ],
    ];
    $all = $copy[$lang] ?? $copy['nl'];
    $c = $all[$state];
    $toonMoment = $state !== 'invalid' && $order?->pickup_date;
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
        .card{background:#fff;max-width:480px;width:100%;border-top:6px solid #F5C518;padding:36px 32px;box-shadow:0 10px 40px rgba(0,0,0,.12);text-align:center;}
        h1{font-size:26px;font-weight:900;margin:0 0 14px;}
        p{font-size:15px;line-height:1.6;color:#333;margin:0 0 24px;}
        .moment{background:#F7F7F4;border-left:4px solid #F5C518;text-align:left;padding:12px 16px;margin:0 0 24px;font-size:15px;}
        .moment small{display:block;font-family:'Courier New',monospace;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#555;margin-bottom:4px;}
        .btn{display:inline-block;background:#0A0A0A;color:#F5C518;font-weight:700;text-decoration:none;padding:13px 26px;border:0;font-size:15px;font-family:inherit;cursor:pointer;}
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $c['t'] }}</h1>
        <p>{{ $c['m'] }}</p>
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
                <button class="btn" type="submit">{{ $c['b'] }}</button>
            </form>
        @else
            <a class="btn" href="https://desnipperaar.nl">{{ $c['b'] }}</a>
        @endif
    </div>
</body>
</html>
