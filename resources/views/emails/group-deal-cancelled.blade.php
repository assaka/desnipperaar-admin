@component('emails._layout', ['title' => 'Groepsdeal afgelast'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Groepsdeal afgelast</h1>

<p>De groepsdeal voor <strong>{{ $deal->city }}</strong> op <strong>{{ $deal->pickup_date->format('l j F Y') }}</strong> gaat helaas niet door.</p>

@if ($deal->cancellation_reason)
<p style="background:#F8F4E2;padding:12px 16px;border-left:3px solid #F5C518;">{{ $deal->cancellation_reason }}</p>
@endif

<p>Er is geen order aangemaakt en je betaalt niets. Wil je je documenten alsnog laten vernietigen? Plaats een individuele bestelling:</p>
<p><a href="https://desnipperaar.nl/order" style="color:#0A0A0A;text-decoration:underline;font-weight:700;">desnipperaar.nl/order</a></p>

<p>Onze excuses voor het ongemak.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
