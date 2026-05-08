@component('emails._layout', ['title' => 'Nieuwe deelnemer in je groepsdeal'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Er heeft zich iemand aangemeld</h1>

<p>Beste {{ explode(' ', $organizer->customer_name)[0] }},</p>

<p>Een nieuwe deelnemer heeft zich ingeschreven voor je groepsdeal in <strong>{{ $deal->city }}</strong> op <strong>{{ $deal->pickup_date->locale('nl')->translatedFormat('l j F Y') }}</strong>.</p>

<table cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse;font-size:14px;margin-bottom:16px;">
  <tr><td style="color:#666;">Voornaam:</td><td><strong>{{ $newFirstName }}</strong></td></tr>
  <tr><td style="color:#666;">Postcode-prefix:</td><td><strong>{{ $newPostcodePrefix }}</strong></td></tr>
  <tr><td style="color:#666;">Brengt:</td><td><strong>{{ $newBoxCount }} dozen</strong>@if ($newContainerCount > 0) en <strong>{{ $newContainerCount }} rolcontainers</strong>@endif</td></tr>
</table>

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Stand van zaken</h2>

<table cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse;font-size:14px;margin-bottom:16px;">
  <tr><td style="color:#666;">Deelnemers:</td><td><strong>{{ $participantCount }}</strong> van maximaal {{ config('desnipperaar.group_deal.max_joiners') }}</td></tr>
  <tr><td style="color:#666;">Voortgang dozen:</td><td><strong>{{ $filledBoxes }}</strong> van {{ $deal->target_box_count }}</td></tr>
  @if ($deal->target_container_count > 0)
    <tr><td style="color:#666;">Voortgang rolcontainers:</td><td><strong>{{ $filledContainers }}</strong> van {{ $deal->target_container_count }}</td></tr>
  @endif
  <tr><td style="color:#666;">Inschrijven sluit:</td><td>{{ $deal->joinCutoffAt()->locale('nl')->translatedFormat('l j F Y') }}</td></tr>
</table>

<p style="margin-top:20px;">
  <a href="{{ $organizer->manageUrl() }}" style="background:#F5C518;color:#0A0A0A;padding:10px 18px;text-decoration:none;font-weight:700;display:inline-block;">Beheer je groepsdeal</a>
</p>

<p style="font-size:12px;color:#777;">Op je beheerpagina zie je de volledige deelnemerslijst en kun je je groepsdoel bijstellen.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
