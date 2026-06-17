@component('emails.fr._layout', ['title' => 'SnipperDag'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Aujourd'hui, c'est le SnipperDag.</h1>

<p>Un jour au hasard chaque semaine, nous offrons {{ $pct }}% de réduction. C'est aujourd'hui. Aujourd'hui seulement, et uniquement pour vous en tant qu'abonné.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:22px 0;background:#0A0A0A;">
    <tr>
        <td style="padding:22px;text-align:center;">
            <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.14em;text-transform:uppercase;color:#F5C518;margin-bottom:8px;">Votre code de réduction</div>
            <div style="font-family:'Courier New',monospace;font-weight:900;font-size:30px;letter-spacing:0.12em;color:#FFFFFF;">{{ $code }}</div>
            <div style="font-size:13px;color:#BBB;margin-top:6px;">{{ $pct }}% de réduction, valable jusqu'à minuit ce soir</div>
        </td>
    </tr>
</table>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 8px;">
    <tr><td align="center">
        <a href="{{ $orderUrl }}" style="display:inline-block;background:#F5C518;color:#0A0A0A;font-weight:900;font-size:16px;text-decoration:none;padding:16px 32px;">Commander avec {{ $pct }}% de réduction</a>
    </td></tr>
</table>

<p style="font-size:13px;color:#555;margin-top:20px;">Le code est actif automatiquement aujourd'hui et expire à minuit. À bientôt pour le prochain SnipperDag.</p>

<p>L'équipe DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    Vous recevez ceci parce que vous vous êtes inscrit au SnipperDag.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Se désinscrire</a>.
</p>
@endcomponent
