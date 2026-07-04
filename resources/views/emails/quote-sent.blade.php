@php $isOffer = !is_null($order->quoted_amount_excl_btw); $ref = $order->quote_reference ?? $order->order_number; @endphp
@component('emails._layout', ['title' => ($isOffer ? 'Offerte ' : 'Bericht ').$ref])
@if ($isOffer)
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Uw offerte is klaar.</h1>
@else
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Een bericht over uw aanvraag.</h1>
@endif

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

@if ($isOffer)
<p>Hierbij onze offerte voor aanvraag <strong style="font-family:monospace;">{{ $ref }}</strong>.</p>
@else
<p>Wij hebben een bericht voor u over aanvraag <strong style="font-family:monospace;">{{ $ref }}</strong>.</p>
@endif

@include('emails._quote_lines_table', ['labels' => ['desc' => 'Omschrijving', 'qty' => 'Aantal', 'price' => 'Prijs', 'subtotal' => 'Subtotaal']])

@if ($order->quoted_amount_excl_btw)
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Bedrag excl. btw</td>
        <td style="padding:8px 0;text-align:right;font-weight:900;font-size:18px;font-family:monospace;">
            € {{ number_format($order->quoted_amount_excl_btw, 2, ',', '.') }}
        </td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Bedrag incl. btw 21%</td>
        <td style="padding:8px 0;text-align:right;font-family:monospace;border-top:1px solid #EEE;">
            € {{ number_format($order->quoted_amount_excl_btw * 1.21, 2, ',', '.') }}
        </td></tr>
    @if ($order->quote_valid_until)
        <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Geldig tot</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">
                {{ $order->quote_valid_until->format('d-m-Y') }}
            </td></tr>
    @endif
</table>
@endif

<div style="white-space:pre-line;font-size:14px;line-height:1.6;background:#F7F7F4;padding:14px;border-left:3px solid #F5C518;margin:16px 0;">{{ $order->quote_body }}</div>

@if ($order->quoted_amount_excl_btw)
<p style="margin:24px 0;text-align:center;">
    <a href="{{ $acceptUrl }}"
       style="display:inline-block;background:#0A0A0A;color:#F5C518;padding:14px 28px;font-weight:900;font-size:16px;text-decoration:none;text-transform:uppercase;letter-spacing:0.05em;">
        Bekijk de offerte →
    </a>
</p>

<p style="font-size:12px;color:#555;">Deze link is persoonlijk en uniek voor uw offerte. Op de vervolgpagina ziet u alle details.
U vult uw adresgegevens in en klikt op <strong>Plaats opdracht</strong>. Pas dan komt een overeenkomst tot stand voor het hierboven genoemde bedrag.
Klikt u niet, dan bent u nergens aan gebonden.</p>
@else
<p style="font-size:12px;color:#555;">U kunt gewoon op deze e-mail antwoorden, dan komt uw reactie direct bij ons binnen.</p>
@endif

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
