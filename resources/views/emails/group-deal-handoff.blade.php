@component('emails._layout', ['title' => 'Je bent nu de organisator'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Je bent nu de organisator</h1>

<p>Beste {{ explode(' ', $newOrganizer->customer_name)[0] }},</p>

<p>De oorspronkelijke organisator van de groepdeal in <strong>{{ $deal->city }}</strong> ({{ $deal->pickup_date->format('l j F Y') }}) heeft zich teruggetrokken. Omdat jij de eerstvolgende deelnemer was, neem jij die rol over.</p>

<p>Wat dat betekent: jouw eerste doos wordt gratis vernietigd (de organisator-perk). Je vastgezette prijs is bijgewerkt. Geen verdere actie nodig; je hoeft niets te doen.</p>

<p>De groepdeal blijft gewoon doorlopen op dezelfde ophaaldag.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
