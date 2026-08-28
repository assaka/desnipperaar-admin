@component('emails._layout', ['title' => 'Zou je een review willen achterlaten?'])
@php
    $voornaam = trim(explode(' ', (string) $order->customer_name)[0]);
    $afzender = $sender?->name ? explode(' ', $sender->name)[0] : 'Hamid';
@endphp
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Zou je een review willen achterlaten?</h1>

<p>Hi {{ $voornaam }},</p>

<p>Nogmaals bedankt voor het vertrouwen en hopelijk is alles naar wens verlopen.
Zou het erg waarderen als je een review zou kunnen plaatsen. Je zou me hier erg mee helpen.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:22px 0;">
    <tr>
        <td align="center">
            <a href="{{ $reviewUrl }}"
               style="display:inline-block;background:#0A0A0A;color:#F5C518;font-weight:900;text-transform:uppercase;letter-spacing:0.06em;font-size:14px;padding:14px 28px;text-decoration:none;">
                Plaats een review
            </a>
        </td>
    </tr>
</table>

<p style="font-size:13px;color:#555;">Werkt de knop niet, dan kan het ook via deze link.<br>
<a href="{{ $reviewUrl }}" style="color:#0A0A0A;">{{ $reviewUrl }}</a></p>

<p>Alvast bedankt en nog een fijne dag!</p>

<p>Vriendelijke groet,<br>{{ $afzender }} - DeSnipperaar</p>
@endcomponent
