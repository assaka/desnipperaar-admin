@component('emails._layout', ['title' => 'Groepsdeal goedgekeurd'])
@php
    $goalText = $deal->target_box_count . ' dozen';
    if ($deal->target_container_count > 0) {
        $goalText .= ' en ' . $deal->target_container_count . ' rolcontainers';
    }
@endphp
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Je groepsdeal staat live</h1>

@if ($deal->organizerParticipant)
<p>Beste {{ explode(' ', $deal->organizerParticipant->customer_name)[0] }},</p>
@endif

<p>Je groepsdeal voor <strong>{{ $deal->city }}</strong> op <strong>{{ $deal->pickup_date->locale('nl')->translatedFormat('l j F Y') }}</strong> is goedgekeurd en publiek zichtbaar op:</p>

<p><a href="https://desnipperaar.nl/groepsdeals/{{ $deal->slug }}" style="color:#0A0A0A;text-decoration:underline;font-weight:700;">desnipperaar.nl/groepsdeals/{{ $deal->slug }}</a></p>

<p>Deel deze pagina via je netwerk: kantoren, MKB-collega's, ZZP'ers, VvE-bestuur of buurorganisaties in {{ $deal->city }}. Particulieren met een archief zijn ook welkom. Het groepsdoel staat op <strong>{{ $goalText }}</strong>; bezoekers zien een voortgangsbalk die meegroeit zodra er deelnemers bijkomen. Inschrijven sluit twee dagen voor de ophaaldag; daarna ontvangt iedereen een orderbevestiging en plannen we de route.</p>

<p>Als organisator vernietigen wij je eerste doos kosteloos.</p>

@if ($deal->organizerParticipant)
<p style="margin-top:20px;">
  <a href="{{ $deal->organizerParticipant->manageUrl() }}" style="background:#F5C518;color:#0A0A0A;padding:10px 18px;text-decoration:none;font-weight:700;display:inline-block;">Beheer je groepsdeal</a>
</p>
<p style="font-size:12px;color:#777;margin-top:6px;">Op deze pagina zie je de actuele deelnemerslijst, kun je je eigen gegevens bijwerken en, als het nodig is, je inschrijving annuleren.</p>
@endif

<p>Vragen? Reply op deze email.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
