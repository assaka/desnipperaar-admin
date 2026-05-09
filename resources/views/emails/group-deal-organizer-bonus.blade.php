@component('emails._layout', ['title' => 'Je organisator-bonus klaar voor uitbetaling'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Je organisator-bonus is klaar voor uitbetaling</h1>

<p>Beste {{ explode(' ', $organizer->customer_name)[0] }},</p>

<p>De groepsdeal in <strong>{{ $deal->city }}</strong> op <strong>{{ $deal->pickup_date->locale('nl')->translatedFormat('l j F Y') }}</strong> is afgesloten en de orders van alle deelnemers zijn aangemaakt. Bedankt voor het bundelen.</p>

<table cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse;font-size:14px;margin:16px 0 24px;">
  <tr>
    <td style="color:#666;">Jouw organisator-bonus ({{ $commissionPct }}% over wat deelnemers betalen):</td>
    <td style="font-weight:900;font-size:18px;">€ {{ number_format($bonusAmount, 2, ',', '.') }}</td>
  </tr>
</table>

@if (!empty($deelnemers))
<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Deelnemers in jouw groepsdeal</h2>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:16px;">
    <tr>
        <th style="text-align:left;padding:6px 0;color:#666;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid #DDD;">Voornaam</th>
        <th style="text-align:right;padding:6px 0;color:#666;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid #DDD;">Dozen</th>
        <th style="text-align:right;padding:6px 0;color:#666;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid #DDD;">Containers</th>
        <th style="text-align:right;padding:6px 0;color:#666;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid #DDD;">Subtotaal</th>
    </tr>
    @foreach ($deelnemers as $d)
        <tr>
            <td style="padding:6px 0;font-size:13px;border-bottom:1px dashed #DDD;">{{ $d['first_name'] }}</td>
            <td style="padding:6px 0;font-size:13px;text-align:right;font-family:'Courier New',monospace;border-bottom:1px dashed #DDD;">{{ $d['box_count'] }}</td>
            <td style="padding:6px 0;font-size:13px;text-align:right;font-family:'Courier New',monospace;border-bottom:1px dashed #DDD;">{{ $d['container_count'] }}</td>
            <td style="padding:6px 0;font-size:13px;text-align:right;font-family:'Courier New',monospace;border-bottom:1px dashed #DDD;">€ {{ number_format($d['subtotal'], 2, ',', '.') }}</td>
        </tr>
    @endforeach
</table>
@endif

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Hoe ontvang je de uitbetaling?</h2>

<p>Reply op deze e-mail met je <strong>IBAN-rekeningnummer</strong> en de naam waarop de rekening staat. Wij maken het bedrag binnen vijf werkdagen na ontvangst van de betalingen door alle deelnemers naar je over.</p>

<p style="background:#F8F4E2;padding:12px 16px;border-left:3px solid #F5C518;font-size:0.95rem;">
  <strong>Privacy:</strong> wij slaan je IBAN niet op in onze systemen. We gebruiken het &eacute;&eacute;nmalig voor de uitbetaling en verwijderen vervolgens de e-mail-thread na afronding.
</p>

<p style="margin-top:24px;">Vragen over de uitbetaling? Reply gewoon op deze e-mail.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
