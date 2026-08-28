@component('emails.en._layout', ['title' => 'Would you leave us a review?'])
@php
    $firstName = trim(explode(' ', (string) $order->customer_name)[0]);
    $fromName = $sender?->name ? explode(' ', $sender->name)[0] : 'Hamid';
@endphp
<p>Hi {{ $firstName }},</p>

<p>Thanks again for your trust and I hope everything went the way you wanted.<br>
I would really appreciate it if you could leave a review. It would help me a lot.</p>

<p style="margin:20px 0;">
    <a href="{{ $reviewUrl }}" style="color:#0A0A0A;font-weight:bold;">{{ $reviewUrl }}</a>
</p>

<p>Thanks in advance and have a good day!</p>

<p>Kind regards,<br>{{ $fromName }}</p>
@endcomponent
