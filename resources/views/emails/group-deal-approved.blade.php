@component('emails._layout', ['title' => 'Groepsdeal goedgekeurd'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Je groepsdeal staat live</h1>

@if ($deal->organizerParticipant)
<p>Beste {{ explode(' ', $deal->organizerParticipant->customer_name)[0] }},</p>
@endif

<p>Goed nieuws: je groepsdeal voor <strong>{{ $deal->city }}</strong> op <strong>{{ $deal->pickup_date->format('l j F Y') }}</strong> is goedgekeurd en zichtbaar op:</p>

<p><a href="https://desnipperaar.nl/groepsdeals/{{ $deal->slug }}" style="color:#0A0A0A;text-decoration:underline;font-weight:700;">desnipperaar.nl/groepsdeals/{{ $deal->slug }}</a></p>

<p>Deel deze pagina met buren en collega's. Het groepsdoel staat op <strong>{{ $deal->target_box_count }} dozen@if ($deal->target_container_count > 0) en {{ $deal->target_container_count }} rolcontainers@endif</strong>; bezoekers zien een voortgangsbalk die meegroeit naarmate er deelnemers bijkomen. Inschrijven sluit twee dagen voor de ophaaldag; daarna ontvangt iedereen een orderbevestiging en plannen we de route.</p>

<p>Je krijgt als organisator je eerste doos gratis vernietigd, tenzij je in de Noord-pilot (1020-1039) zit; daar geldt al een 20% korting die de perk vervangt.</p>

<p>Vragen? Reply op deze email.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
