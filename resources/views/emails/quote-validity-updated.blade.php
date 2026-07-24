@php $ref = $order->quote_reference ?? $order->order_number; @endphp
@component('emails._layout', ['title' => 'Offerte '.$ref])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">De geldigheid van uw offerte is aangepast.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>De geldigheid van onze offerte <strong style="font-family:monospace;">{{ $ref }}</strong> is verlengd. U kunt de offerte nog accepteren tot de nieuwe datum hieronder.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    @if ($order->quoted_amount_excl_btw)
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Bedrag excl. btw</td>
        <td style="padding:8px 0;text-align:right;font-weight:900;font-size:18px;font-family:monospace;">
            € {{ number_format($order->quoted_amount_excl_btw, 2, ',', '.') }}
        </td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Bedrag incl. btw 21%</td>
        <td style="padding:8px 0;text-align:right;font-family:monospace;border-top:1px solid #EEE;">
            € {{ number_format($order->quoted_amount_excl_btw * 1.21, 2, ',', '.') }}
        </td></tr>
    @endif
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Nieuwe geldig tot</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">
            {{ $order->quote_valid_until->format('d-m-Y') }}
        </td></tr>
</table>

<p style="margin:24px 0;text-align:center;">
    <a href="{{ $acceptUrl }}"
       style="display:inline-block;background:#0A0A0A;color:#F5C518;padding:14px 28px;font-weight:900;font-size:16px;text-decoration:none;text-transform:uppercase;letter-spacing:0.05em;">
        Bekijk de offerte →
    </a>
</p>

<p style="font-size:12px;color:#555;">Deze link is persoonlijk en uniek voor uw offerte. Op de vervolgpagina ziet u alle details. U vult uw adresgegevens in en klikt op <strong>Plaats opdracht</strong>. Pas dan komt een overeenkomst tot stand voor het hierboven genoemde bedrag. Klikt u niet, dan bent u nergens aan gebonden.</p>

<p style="font-size:12px;color:#555;">Heeft u vragen of wilt u iets aanpassen? Beantwoord dan gewoon deze e-mail. <strong>Houd het onderwerp ongewijzigd</strong>, dan wordt uw bericht automatisch toegevoegd aan uw offerte.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
