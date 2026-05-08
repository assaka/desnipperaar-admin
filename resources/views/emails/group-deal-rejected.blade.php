@component('emails._layout', ['title' => 'Groepdeal niet doorgegaan'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Je groepdeal-aanvraag</h1>

@if ($deal->organizerParticipant)
<p>Beste {{ explode(' ', $deal->organizerParticipant->customer_name)[0] }},</p>
@endif

<p>Helaas kunnen wij je groepdeal-aanvraag voor <strong>{{ $deal->city }}</strong> op <strong>{{ $deal->pickup_date->format('l j F Y') }}</strong> nu niet inplannen.</p>

@if ($deal->cancellation_reason)
<p style="background:#F8F4E2;padding:12px 16px;border-left:3px solid #F5C518;">{{ $deal->cancellation_reason }}</p>
@endif

<p>Wil je toch documenten laten vernietigen? Een individuele offerte kan altijd via:</p>
<p><a href="https://desnipperaar.nl/order" style="color:#0A0A0A;text-decoration:underline;font-weight:700;">desnipperaar.nl/order</a></p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
