@component('emails._layout', ['title' => 'Je groepsdeal-voorstel is ontvangen'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Bedankt, je voorstel staat in de wachtrij</h1>

@if ($deal->organizerParticipant)
<p>Beste {{ explode(' ', $deal->organizerParticipant->customer_name)[0] }},</p>
@endif

<p>We hebben je groepsdeal-voorstel voor <strong>{{ $deal->city }}</strong> op <strong>{{ $deal->pickup_date->format('l j F Y') }}</strong> goed ontvangen.</p>

<table cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse;font-size:14px;">
  <tr><td style="color:#666;">Stad:</td><td><strong>{{ $deal->city }}</strong></td></tr>
  <tr><td style="color:#666;">Voorgestelde ophaaldag:</td><td><strong>{{ $deal->pickup_date->format('l j F Y') }}</strong></td></tr>
  <tr><td style="color:#666;">Doel dozen:</td><td><strong>{{ $deal->target_box_count }}</strong></td></tr>
  @if ($deal->target_container_count > 0)
    <tr><td style="color:#666;">Doel rolcontainers:</td><td><strong>{{ $deal->target_container_count }}</strong></td></tr>
  @endif
</table>

<p style="margin-top:20px;">We bekijken je voorstel binnen één werkdag. Zodra het is goedgekeurd, sturen we je een aparte mail met de publieke link. Vanaf dat moment kun je het delen via je netwerk: kantoren, MKB-collega's, ZZP'ers, VvE's en particulieren met een archief in {{ $deal->city }} kunnen aansluiten.</p>

@if ($deal->organizerParticipant)
<p style="margin-top:20px;">
  <a href="{{ $deal->organizerParticipant->manageUrl() }}" style="background:#F5C518;color:#0A0A0A;padding:10px 18px;text-decoration:none;font-weight:700;display:inline-block;">Bekijk of wijzig je voorstel</a>
</p>
<p style="font-size:12px;color:#777;margin-top:6px;">Bewaar deze link goed: na goedkeuring zie je hier ook de deelnemers die zich aansluiten.</p>
@endif

<p>Vragen? Reply op deze email.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
