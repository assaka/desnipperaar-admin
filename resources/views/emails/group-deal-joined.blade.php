@component('emails._layout', ['title' => 'Welkom in de groepsdeal'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Je doet mee aan de groepsdeal</h1>

<p>Beste {{ explode(' ', $participant->customer_name)[0] }},</p>

<p>Je bent ingeschreven voor de groepsdeal in <strong>{{ $deal->city }}</strong>, ophaaldag <strong>{{ $deal->pickup_date->format('l j F Y') }}</strong>.</p>

<table cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse;font-size:14px;">
  <tr><td style="color:#666;">Deelnemers tot nu toe:</td><td><strong>{{ $deal->participants_count }}</strong></td></tr>
  <tr><td style="color:#666;">Inschrijven sluit:</td><td><strong>{{ $deal->joinCutoffAt()->format('l j F Y') }}</strong></td></tr>
  <tr><td style="color:#666;">Adres:</td><td>{{ $participant->customer_address }}, {{ $participant->customer_postcode }} {{ $participant->customer_city }}</td></tr>
  <tr><td style="color:#666;">Dozen / containers:</td><td>{{ $participant->box_count }} / {{ $participant->container_count }}</td></tr>
</table>

<h2 style="font-size:16px;margin:24px 0 8px;">Vastgezette prijs</h2>
<p>Deze prijs blijft staan ongeacht wijzigingen in onze tarieven tussen nu en de ophaaldag.</p>

<table cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse;font-size:14px;width:100%;max-width:420px;">
  <tr><td style="color:#666;">Subtotaal (excl. btw):</td><td align="right">€ {{ number_format($snapshot['subtotal'], 2, ',', '.') }}</td></tr>
  @if (($snapshot['discount'] ?? 0) > 0)
    <tr><td style="color:#666;">Waarvan korting:</td><td align="right" style="color:#0A8A4F;">- € {{ number_format($snapshot['discount'], 2, ',', '.') }}</td></tr>
  @endif
  <tr><td style="color:#666;">BTW 21%:</td><td align="right">€ {{ number_format($snapshot['vat'], 2, ',', '.') }}</td></tr>
  <tr><td style="color:#0A0A0A;font-weight:700;border-top:1px solid #DDD;padding-top:8px;">Totaal incl. btw:</td><td align="right" style="font-weight:700;border-top:1px solid #DDD;padding-top:8px;">€ {{ number_format($snapshot['total'], 2, ',', '.') }}</td></tr>
</table>

<p style="margin-top:20px;">Twee dagen voor de ophaaldag sluit de inschrijving en ontvang je een orderbevestiging met de definitieve planning.</p>

<p>Vragen? Reply op deze email.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
