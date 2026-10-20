@component('emails._layout', ['title' => 'Profiteer nog van '.$oldPct.'% korting'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Nog even {{ $oldPct }}% korting voor u.</h1>

<p>Als abonnee krijgt u op de SnipperDag korting op het vernietigen van uw papier en datadragers. Vanaf 1 november wordt die korting {{ $newPct }}%.</p>

<p>Tot die tijd geldt nog de volle {{ $oldPct }}%. Er komt nog één SnipperDag voor 1 november. Die dag krijgt u van ons een mail met uw code.</p>

<p>Bestelt u op die dag, dan profiteert u nog van {{ $oldPct }}% korting. Het ophalen plannen we daarna samen in, ook als dat pas in november is.</p>

<p>Staan er nog dozen met oude administratie, personeelsdossiers of een harde schijf die weg moet? Zet ze alvast klaar en houd uw inbox in de gaten.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    U ontvangt dit omdat u zich heeft aangemeld voor de SnipperDag.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Afmelden</a>.
</p>
@endcomponent
