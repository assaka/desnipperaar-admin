@component('emails.en._layout', ['title' => 'Still '.$oldPct.'% off for you'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Still {{ $oldPct }}% off for you, for now.</h1>

<p>As a subscriber you get a discount on destroying your paper and data carriers on the DestructionDay. From 1 November that discount becomes {{ $newPct }}%.</p>

<p>Until then the full {{ $oldPct }}% still applies. There is one more DestructionDay before 1 November. On that day we will email you your code.</p>

<p>Order on that day and you still get {{ $oldPct }}% off. We schedule the pickup together afterwards, even if that is in November.</p>

<p>Do you still have boxes of old records, personnel files or a hard drive that needs to go? Get them ready and keep an eye on your inbox.</p>

<p>Kind regards,<br>Team DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    You receive this because you signed up for the DestructionDay.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Unsubscribe</a>.
</p>
@endcomponent
