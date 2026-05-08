@component('emails._layout', ['title' => 'Nieuwe groepsdeal-aanvraag'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Nieuwe groepsdeal-aanvraag</h1>

<p>Een klant heeft een groepsdeal voorgesteld. Beoordeel + keur goed of wijs af in de admin.</p>

<table cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse;font-size:14px;">
  <tr><td style="color:#666;">Stad:</td><td><strong>{{ $deal->city }}</strong></td></tr>
  <tr><td style="color:#666;">Ophaaldag:</td><td><strong>{{ $deal->pickup_date->format('l j F Y') }}</strong></td></tr>
  <tr><td style="color:#666;">Slug:</td><td><code>{{ $deal->slug }}</code></td></tr>
  @if ($deal->organizerParticipant)
    <tr><td style="color:#666;">Organisator:</td><td>{{ $deal->organizerParticipant->customer_name }} &lt;{{ $deal->organizerParticipant->customer_email }}&gt;</td></tr>
    <tr><td style="color:#666;">Postcode:</td><td>{{ $deal->organizerParticipant->customer_postcode }}</td></tr>
    <tr><td style="color:#666;">Adres:</td><td>{{ $deal->organizerParticipant->customer_address }}</td></tr>
    <tr><td style="color:#666;">Dozen / containers:</td><td>{{ $deal->organizerParticipant->box_count }} / {{ $deal->organizerParticipant->container_count }}</td></tr>
  @endif
</table>

<p style="margin-top:24px;">
  <a href="https://admin.desnipperaar.nl/group-deals/{{ $deal->id }}" style="background:#F5C518;color:#0A0A0A;padding:10px 18px;text-decoration:none;font-weight:700;display:inline-block;">Open in admin</a>
</p>
@endcomponent
