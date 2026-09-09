@php
    // Three states. 'confirm' asks before doing anything, because a mail
    // scanner following the link must not be able to opt someone out.
    $copy = [
        'nl' => [
            'confirm' => ['t' => 'Afmelden voor de SnipperDag', 'm' => 'Wilt u geen SnipperDag e-mails meer ontvangen? Bevestig het hieronder.', 'b' => 'Ja, meld mij af'],
            'done'    => ['t' => 'Afgemeld', 'm' => 'U ontvangt geen SnipperDag e-mails meer. Jammer dat u gaat, u bent altijd welkom terug.', 'b' => 'Naar desnipperaar.nl'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'Deze afmeldlink is niet geldig.', 'b' => 'desnipperaar.nl'],
        ],
        'en' => [
            'confirm' => ['t' => 'Unsubscribe from the DestructionDay', 'm' => 'Do you want to stop receiving DestructionDay e-mails? Please confirm below.', 'b' => 'Yes, unsubscribe me'],
            'done'    => ['t' => 'Unsubscribed', 'm' => 'You will no longer receive DestructionDay e-mails. Sorry to see you go, you are always welcome back.', 'b' => 'Go to desnipperaar.nl'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'This unsubscribe link is not valid.', 'b' => 'desnipperaar.nl'],
        ],
        'fr' => [
            'confirm' => ['t' => 'Se désinscrire du Jour de Destruction', 'm' => 'Vous ne souhaitez plus recevoir les e-mails Jour de Destruction ? Confirmez ci-dessous.', 'b' => 'Oui, désinscrivez-moi'],
            'done'    => ['t' => 'Désinscrit', 'm' => 'Vous ne recevrez plus d\'e-mails Jour de Destruction. Désolé de vous voir partir, vous êtes toujours le bienvenu.', 'b' => 'Aller sur desnipperaar.nl'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'Ce lien de désinscription n\'est pas valide.', 'b' => 'desnipperaar.nl'],
        ],
        'es' => [
            'confirm' => ['t' => 'Darse de baja del Día de Destrucción', 'm' => '¿Ya no quiere recibir correos del Día de Destrucción? Confírmelo abajo.', 'b' => 'Sí, darme de baja'],
            'done'    => ['t' => 'Dado de baja', 'm' => 'Ya no recibirá correos de Día de Destrucción. Lamentamos que se vaya, siempre es bienvenido de nuevo.', 'b' => 'Ir a desnipperaar.nl'],
            'invalid' => ['t' => 'DeSnipperaar', 'm' => 'Este enlace para darse de baja no es válido.', 'b' => 'desnipperaar.nl'],
        ],
    ];
    $c = ($copy[$lang] ?? $copy['nl'])[$state];
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
        .btn{display:inline-block;background:#0A0A0A;color:#F5C518;font-weight:700;text-decoration:none;padding:13px 26px;border:0;font-size:15px;font-family:inherit;cursor:pointer;}
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $c['t'] }}</h1>
        <p>{{ $c['m'] }}</p>
        @if ($state === 'confirm')
            {{-- Host-relative on purpose. The page is proxied in under
                 desnipperaar.nl, so an absolute URL built from APP_URL would
                 post to admin.desnipperaar.nl and expose the admin host. --}}
            <form method="post" action="/afmelden/{{ rawurlencode($token) }}">
                <input type="hidden" name="confirm" value="1">
                <input type="hidden" name="lang" value="{{ $lang }}">
                <button class="btn" type="submit">{{ $c['b'] }}</button>
            </form>
        @else
            <a class="btn" href="https://desnipperaar.nl">{{ $c['b'] }}</a>
        @endif
    </div>
</body>
</html>
