@php $isOffer = !is_null($order->quoted_amount_excl_btw); $ref = $order->quote_reference ?? $order->order_number; @endphp
@component('emails.en._layout', ['title' => ($isOffer ? 'Quote ' : 'Message ').$ref])
@if ($isOffer)
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Your quote is ready.</h1>
@else
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">A message about your request.</h1>
@endif

<p>Dear {{ explode(' ', $order->customer_name)[0] }},</p>

@if ($isOffer)
<p>Here is our quote for request <strong style="font-family:monospace;">{{ $ref }}</strong>.</p>
@else
<p>We have a message for you about request <strong style="font-family:monospace;">{{ $ref }}</strong>.</p>
@endif

@include('emails._quote_lines_table', ['labels' => ['desc' => 'Description', 'qty' => 'Qty', 'price' => 'Price', 'subtotal' => 'Subtotal', 'optional' => 'optional', 'optional_note' => 'Lines marked optional can be toggled on the quote page; the total updates accordingly.']])

@if ($order->quoted_amount_excl_btw)
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Amount excl. VAT</td>
        <td style="padding:8px 0;text-align:right;font-weight:900;font-size:18px;font-family:monospace;">
            € {{ number_format($order->quoted_amount_excl_btw, 2, ',', '.') }}
        </td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Amount incl. 21% VAT</td>
        <td style="padding:8px 0;text-align:right;font-family:monospace;border-top:1px solid #EEE;">
            € {{ number_format($order->quoted_amount_excl_btw * 1.21, 2, ',', '.') }}
        </td></tr>
    @if ($order->quote_valid_until)
        <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Valid until</td>
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
        View the quote →
    </a>
</p>

<p style="font-size:12px;color:#555;">This link is personal and unique to your quote. The next page shows every detail.
You fill in your address and click <strong>Place order</strong>. Only then do you enter into an agreement for the amount stated above.
If you do not click, you are under no obligation.</p>
@else
<p style="font-size:12px;color:#555;">You can simply reply to this email and your message will reach us directly.</p>
@endif

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
