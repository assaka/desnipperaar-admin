@component('emails._layout', ['title' => 'Welkom bij DeSnipperaar Dag'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">U bent aangemeld.</h1>

<p>Bedankt voor uw aanmelding voor DeSnipperaar Dag. Eén willekeurige dag per week geven wij {{ $pct }}% korting. U bent als eerste op de hoogte zodra die dag er is.</p>

<p>Houd uw inbox in de gaten. Tot snel.</p>

<p>Team DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    U ontvangt dit omdat u zich aanmeldde voor DeSnipperaar Dag.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Afmelden</a>.
</p>
@endcomponent
