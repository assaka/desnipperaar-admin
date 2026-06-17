@php
    $copy = [
        'nl' => ['t' => 'Afgemeld', 'm' => 'U ontvangt geen SnipperDag e-mails meer. Jammer dat u gaat, u bent altijd welkom terug.', 'b' => 'Naar desnipperaar.nl'],
        'en' => ['t' => 'Unsubscribed', 'm' => 'You will no longer receive SnipperDag e-mails. Sorry to see you go, you are always welcome back.', 'b' => 'Go to desnipperaar.nl'],
        'fr' => ['t' => 'Désinscrit', 'm' => 'Vous ne recevrez plus d\'e-mails SnipperDag. Désolé de vous voir partir, vous êtes toujours le bienvenu.', 'b' => 'Aller sur desnipperaar.nl'],
        'es' => ['t' => 'Dado de baja', 'm' => 'Ya no recibirá correos de SnipperDag. Lamentamos que se vaya, siempre es bienvenido de nuevo.', 'b' => 'Ir a desnipperaar.nl'],
    ];
    $notFound = [
        'nl' => 'Deze afmeldlink is niet geldig.',
        'en' => 'This unsubscribe link is not valid.',
        'fr' => 'Ce lien de désinscription n\'est pas valide.',
        'es' => 'Este enlace para darse de baja no es válido.',
    ];
    $c = $copy[$lang] ?? $copy['nl'];
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $found ? $c['t'] : 'DeSnipperaar' }}</title>
    <style>
        body{margin:0;background:#EEECE4;font-family:Arial,Helvetica,sans-serif;color:#0A0A0A;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:20px;}
        .card{background:#fff;max-width:480px;width:100%;border-top:6px solid #F5C518;padding:36px 32px;box-shadow:0 10px 40px rgba(0,0,0,.12);text-align:center;}
        h1{font-size:26px;font-weight:900;margin:0 0 14px;}
        p{font-size:15px;line-height:1.6;color:#333;margin:0 0 24px;}
        a.btn{display:inline-block;background:#0A0A0A;color:#F5C518;font-weight:700;text-decoration:none;padding:13px 26px;}
    </style>
</head>
<body>
    <div class="card">
        @if ($found)
            <h1>{{ $c['t'] }}</h1>
            <p>{{ $c['m'] }}</p>
            <a class="btn" href="https://desnipperaar.nl">{{ $c['b'] }}</a>
        @else
            <h1>DeSnipperaar</h1>
            <p>{{ $notFound[$lang] ?? $notFound['nl'] }}</p>
            <a class="btn" href="https://desnipperaar.nl">desnipperaar.nl</a>
        @endif
    </div>
</body>
</html>
