@component('emails._layout', ['title' => 'DeSnipperaar Dag'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Vandaag is het DeSnipperaar Dag.</h1>

<p>Eén willekeurige dag per week geven wij {{ $pct }}% korting. Vandaag is die dag. Alleen vandaag, alleen voor u als abonnee.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:22px 0;background:#0A0A0A;">
    <tr>
        <td style="padding:22px;text-align:center;">
            <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.14em;text-transform:uppercase;color:#F5C518;margin-bottom:8px;">Uw kortingscode</div>
            <div style="font-family:'Courier New',monospace;font-weight:900;font-size:30px;letter-spacing:0.12em;color:#FFFFFF;">{{ $code }}</div>
            <div style="font-size:13px;color:#BBB;margin-top:6px;">{{ $pct }}% korting, geldig tot vanavond middernacht</div>
        </td>
    </tr>
</table>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 8px;">
    <tr><td align="center">
        <a href="{{ $orderUrl }}" style="display:inline-block;background:#F5C518;color:#0A0A0A;font-weight:900;font-size:16px;text-decoration:none;padding:16px 32px;">Bestel nu met {{ $pct }}% korting</a>
    </td></tr>
</table>

<p style="font-size:13px;color:#555;margin-top:20px;">De code is vandaag automatisch actief en vervalt om middernacht. Tot de volgende DeSnipperaar Dag.</p>

<p>Team DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    U ontvangt dit omdat u zich aanmeldde voor DeSnipperaar Dag.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Afmelden</a>.
</p>
@endcomponent
