@component('emails.en._layout', ['title' => 'Welcome to SnipperDag'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">You are signed up.</h1>

<p>Thanks for signing up for SnipperDag. One random day each week we give {{ $pct }}% off. You will be the first to know when that day arrives.</p>

<p>Keep an eye on your inbox. See you soon.</p>

<p>Team DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    You receive this because you signed up for SnipperDag.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Unsubscribe</a>.
</p>
@endcomponent
