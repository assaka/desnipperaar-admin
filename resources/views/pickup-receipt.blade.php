@php
    // Vier toestanden. 'confirm' vraagt eerst, want een mailscanner die de link
    // volgt mag niets afvinken. Na een antwoord toont de pagina dat antwoord.
    $copy = [
        'nl' => [
            'confirm'   => ['t' => 'Past dit ophaalmoment?', 'm' => 'Laat ons weten of dit moment u uitkomt.'],
            'akkoord'   => ['t' => 'Bedankt', 'm' => 'Fijn dat het past. Tot dan.'],
            'past_niet' => ['t' => 'Bedankt voor het laten weten', 'm' => 'Wij nemen zo snel mogelijk contact met u op om een ander moment af te spreken. Liever direct bellen? 06-10229965.'],
            'invalid'   => ['t' => 'DeSnipperaar', 'm' => 'Deze link is niet (meer) geldig. Vragen? Bel 06-10229965.'],
            'moment' => 'Ophaalmoment', 'ja' => 'Ja, dit past', 'nee_open' => 'Past dit moment niet?',
            'nee_label' => 'Welke dagen of tijden komen beter uit? (optioneel)', 'nee' => 'Verstuur: past niet', 'site' => 'Naar desnipperaar.nl',
        ],
        'en' => [
            'confirm'   => ['t' => 'Does this pickup slot suit you?', 'm' => 'Let us know whether this slot works for you.'],
            'akkoord'   => ['t' => 'Thank you', 'm' => 'Great, see you then.'],
            'past_niet' => ['t' => 'Thanks for letting us know', 'm' => 'We will contact you as soon as possible to arrange another slot. Prefer to call? +31 6 10229965.'],
            'invalid'   => ['t' => 'DeSnipperaar', 'm' => 'This link is not (or no longer) valid. Questions? Call +31 6 10229965.'],
            'moment' => 'Pickup', 'ja' => 'Yes, this works', 'nee_open' => 'Doesn\'t this slot work?',
            'nee_label' => 'Which days or times suit you better? (optional)', 'nee' => 'Send: doesn\'t work', 'site' => 'Go to desnipperaar.nl',
        ],
        'fr' => [
            'confirm'   => ['t' => 'Ce créneau vous convient-il ?', 'm' => 'Dites-nous si ce créneau d\'enlèvement vous convient.'],
            'akkoord'   => ['t' => 'Merci', 'm' => 'Parfait, à bientôt.'],
            'past_niet' => ['t' => 'Merci de nous avoir prévenus', 'm' => 'Nous vous contacterons au plus vite pour convenir d\'un autre créneau. Vous préférez appeler ? +31 6 10229965.'],
            'invalid'   => ['t' => 'DeSnipperaar', 'm' => 'Ce lien n\'est pas (ou plus) valide. Des questions ? Appelez le +31 6 10229965.'],
            'moment' => 'Enlèvement', 'ja' => 'Oui, ça me convient', 'nee_open' => 'Ce créneau ne vous convient pas ?',
            'nee_label' => 'Quels jours ou horaires vous conviennent mieux ? (facultatif)', 'nee' => 'Envoyer : ne convient pas', 'site' => 'Aller sur desnipperaar.nl',
        ],
        'es' => [
            'confirm'   => ['t' => '¿Le viene bien este momento?', 'm' => 'Indíquenos si este momento de recogida le conviene.'],
            'akkoord'   => ['t' => 'Gracias', 'm' => 'Perfecto, hasta entonces.'],
            'past_niet' => ['t' => 'Gracias por avisarnos', 'm' => 'Nos pondremos en contacto con usted lo antes posible para acordar otro momento. ¿Prefiere llamar? +31 6 10229965.'],
            'invalid'   => ['t' => 'DeSnipperaar', 'm' => 'Este enlace no es (o ya no es) válido. ¿Preguntas? Llame al +31 6 10229965.'],
            'moment' => 'Recogida', 'ja' => 'Sí, me viene bien', 'nee_open' => '¿No le viene bien?',
            'nee_label' => '¿Qué días u horas le vienen mejor? (opcional)', 'nee' => 'Enviar: no me viene bien', 'site' => 'Ir a desnipperaar.nl',
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
        .card{background:#fff;max-width:480px;width:100%;border-top:6px solid #F5C518;padding:36px 32px;box-shadow:0 10px 40px rgba(0,0,0,.12);text-align:center;box-sizing:border-box;}
        h1{font-size:26px;font-weight:900;margin:0 0 14px;}
        p{font-size:15px;line-height:1.6;color:#333;margin:0 0 24px;}
        .moment{background:#F7F7F4;border-left:4px solid #F5C518;text-align:left;padding:12px 16px;margin:0 0 24px;font-size:15px;}
        .moment small{display:block;font-family:'Courier New',monospace;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#555;margin-bottom:4px;}
        .btn{display:inline-block;background:#0A0A0A;color:#F5C518;font-weight:700;text-decoration:none;padding:13px 26px;border:0;font-size:15px;font-family:inherit;cursor:pointer;}
        .btn-alt{background:#fff;color:#0A0A0A;border:2px solid #0A0A0A;padding:11px 24px;}
        details{margin-top:24px;text-align:left;border-top:1px solid #E5E5E0;padding-top:18px;}
        summary{cursor:pointer;font-weight:700;font-size:15px;text-align:center;}
        label{display:block;font-size:14px;color:#333;margin:14px 0 6px;}
        textarea{width:100%;box-sizing:border-box;min-height:90px;font:inherit;font-size:14px;padding:10px;border:1px solid #CCC;}
        details form{text-align:center;}
        details .btn{margin-top:12px;}
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
                <input type="hidden" name="antwoord" value="akkoord">
                <button class="btn" type="submit">{{ $all['ja'] }}</button>
            </form>
            <details @if ($gekozen === 'past_niet') open @endif>
                <summary>{{ $all['nee_open'] }}</summary>
                <form method="post" action="/ontvangen/{{ rawurlencode($token) }}">
                    <input type="hidden" name="lang" value="{{ $lang }}">
                    <input type="hidden" name="antwoord" value="past_niet">
                    <label for="note">{{ $all['nee_label'] }}</label>
                    <textarea id="note" name="note" maxlength="2000"></textarea>
                    <button class="btn btn-alt" type="submit">{{ $all['nee'] }}</button>
                </form>
            </details>
        @else
            <a class="btn" href="https://desnipperaar.nl">{{ $all['site'] }}</a>
        @endif
    </div>
</body>
</html>
