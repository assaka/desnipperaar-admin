@component('emails._layout', ['title' => 'Welkom in de groepsdeal'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Je doet mee aan de groepsdeal</h1>

<p>Beste {{ explode(' ', $participant->customer_name)[0] }},</p>

<p>Je bent ingeschreven voor de groepsdeal in <strong>{{ $deal->city }}</strong>, ophaaldag <strong>{{ $deal->pickup_date->locale('nl')->translatedFormat('l j F Y') }}</strong>.</p>

@php
    $filledBoxes = $deal->participants()->sum('box_count');
    $filledContainers = $deal->participants()->sum('container_count');
@endphp
<table cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse;font-size:14px;">
  <tr><td style="color:#666;">Deelnemers tot nu toe:</td><td><strong>{{ $deal->participants_count }}</strong></td></tr>
  <tr><td style="color:#666;">Voortgang dozen:</td><td><strong>{{ $filledBoxes }}</strong> van {{ $deal->target_box_count }}</td></tr>
  @if ($deal->target_container_count > 0)
    <tr><td style="color:#666;">Voortgang rolcontainers:</td><td><strong>{{ $filledContainers }}</strong> van {{ $deal->target_container_count }}</td></tr>
  @endif
  <tr><td style="color:#666;">Inschrijven sluit:</td><td><strong>{{ $deal->joinCutoffAt()->locale('nl')->translatedFormat('l j F Y') }}</strong></td></tr>
  <tr><td style="color:#666;">Adres:</td><td>{{ $participant->customer_address }}, {{ $participant->customer_postcode }} {{ $participant->customer_city }}</td></tr>
  <tr><td style="color:#666;">Dozen / containers:</td><td>{{ $participant->box_count }} / {{ $participant->container_count }}</td></tr>
</table>

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Vaste prijs</h2>

<p style="font-size:13px;color:#555;margin:0 0 12px;">Deze prijs blijft staan ongeacht wijzigingen in onze tarieven tussen nu en de ophaaldag.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:16px;">
    @foreach (($snapshot['lines'] ?? []) as $line)
        <tr>
            <td style="padding:6px 0;color:#333;font-size:13px;border-bottom:1px dashed #DDD;">{{ $line['label'] }}</td>
            <td style="padding:6px 0;color:#666;font-size:12px;border-bottom:1px dashed #DDD;text-align:center;font-family:'Courier New',monospace;white-space:nowrap;">
                {{ $line['qty'] }} &times; € {{ number_format($line['unit'], 2, ',', '.') }}
            </td>
            <td style="padding:6px 0;font-weight:700;font-size:13px;border-bottom:1px dashed #DDD;text-align:right;font-family:'Courier New',monospace;white-space:nowrap;">
                € {{ number_format($line['was_subtotal'] ?? $line['subtotal'], 2, ',', '.') }}
            </td>
        </tr>
    @endforeach

    @foreach (($snapshot['media_lines'] ?? []) as $line)
        <tr>
            <td style="padding:6px 0;color:#333;font-size:13px;border-bottom:1px dashed #DDD;">{{ $line['label'] }}</td>
            <td style="padding:6px 0;color:#666;font-size:12px;border-bottom:1px dashed #DDD;text-align:center;font-family:'Courier New',monospace;white-space:nowrap;">
                {{ $line['qty'] }} &times; € {{ number_format($line['unit'], 2, ',', '.') }}
            </td>
            <td style="padding:6px 0;font-weight:700;font-size:13px;border-bottom:1px dashed #DDD;text-align:right;font-family:'Courier New',monospace;white-space:nowrap;">
                € {{ number_format($line['subtotal'], 2, ',', '.') }}
            </td>
        </tr>
    @endforeach

    <tr>
        <td style="padding:10px 0 4px;color:#555;font-size:12px;" colspan="2">Subtotaal (excl. btw)</td>
        <td style="padding:10px 0 4px;font-family:'Courier New',monospace;text-align:right;font-size:13px;">€ {{ number_format($snapshot['subtotal_regular'] ?? $snapshot['subtotal'], 2, ',', '.') }}</td>
    </tr>
    @if (!empty($snapshot['discount']) && $snapshot['discount'] > 0)
        <tr>
            <td style="padding:4px 0;color:#2E7D32;font-size:12px;" colspan="2">Waarvan korting</td>
            <td style="padding:4px 0;font-family:'Courier New',monospace;text-align:right;font-size:13px;color:#2E7D32;">&minus; € {{ number_format($snapshot['discount'], 2, ',', '.') }}</td>
        </tr>
    @endif
    <tr>
        <td style="padding:4px 0;color:#555;font-size:12px;" colspan="2">BTW 21%</td>
        <td style="padding:4px 0;font-family:'Courier New',monospace;text-align:right;font-size:13px;">€ {{ number_format($snapshot['vat'], 2, ',', '.') }}</td>
    </tr>
    <tr>
        <td style="padding:10px 0 4px;font-weight:900;font-size:15px;border-top:2px solid #0A0A0A;" colspan="2">Totaal incl. btw</td>
        <td style="padding:10px 0 4px;font-weight:900;font-size:16px;border-top:2px solid #0A0A0A;text-align:right;font-family:'Courier New',monospace;">€ {{ number_format($snapshot['total'], 2, ',', '.') }}</td>
    </tr>
</table>

@if ($participant->id === $deal->organizer_participant_id)
    <p style="background:#0A0A0A;color:#F5C518;padding:6px 10px;display:inline-block;font-size:12px;font-weight:700;margin:0 0 16px;">
        ✨ Als organisator &middot; eerste doos gratis
    </p>
@endif

<p style="margin-top:20px;">Twee dagen voor de ophaaldag sluit de inschrijving en ontvang je een orderbevestiging met de definitieve planning.</p>

<p style="margin-top:20px;">
  <a href="{{ $participant->manageUrl() }}" style="background:#F5C518;color:#0A0A0A;padding:10px 18px;text-decoration:none;font-weight:700;display:inline-block;">Bekijk of wijzig je inschrijving</a>
</p>
<p style="font-size:12px;color:#777;margin-top:6px;">Of bewaar deze link: <a href="{{ $participant->manageUrl() }}" style="color:#777;">{{ $participant->manageUrl() }}</a></p>

<p>Vragen? Reply op deze email.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
