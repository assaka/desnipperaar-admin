@component('emails._layout', ['title' => 'De SnipperDag verandert'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Nog één keer {{ $oldPct }}% korting.</h1>

<p>Vanaf 1 november geeft de SnipperDag {{ $newPct }}% korting. Tot die tijd is dat nog {{ $oldPct }}%.</p>

<p>Voor 1 november komt er nog één SnipperDag. Op die dag krijgt u zoals altijd een mail met uw code voor {{ $oldPct }}% korting. Bestelt u die dag, dan houdt u die korting. Het ophalen mag ook later.</p>

<p>Ligt er nog papier, archief of een oude harde schijf die weg moet? Dan is dit het moment.</p>

<p>Ook na 1 november hoort u als eerste wanneer het SnipperDag is.</p>

<p>Team DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    U ontvangt dit omdat u zich heeft aangemeld voor de SnipperDag.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Afmelden</a>.
</p>
@endcomponent
