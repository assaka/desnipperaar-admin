@component('emails.en._layout', ['title' => 'The DestructionDay is changing'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">One more time {{ $oldPct }}% off.</h1>

<p>From 1 November the DestructionDay gives {{ $newPct }}% off. Until then it is still {{ $oldPct }}%.</p>

<p>There is one more DestructionDay before 1 November. On that day you will get your usual email with a code for {{ $oldPct }}% off. Order that day and you keep that discount. The pickup can be later.</p>

<p>Still have paper, archives or an old hard drive that needs to go? Now is the time.</p>

<p>After 1 November you will still be the first to know when it is DestructionDay.</p>

<p>Team DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    You receive this because you signed up for the DestructionDay.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Unsubscribe</a>.
</p>
@endcomponent
