@php $ref = $order->quote_reference ?? $order->order_number; @endphp
@component('emails._layout', ['title' => 'Quote '.$ref])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">The validity of your quote has been updated.</h1>

<p>Dear {{ explode(' ', $order->customer_name)[0] }},</p>

<p>The validity of our quote <strong style="font-family:monospace;">{{ $ref }}</strong> has been extended. You can still accept it until the new date below.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    @if ($order->quoted_amount_excl_btw)
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Amount excl. VAT</td>
        <td style="padding:8px 0;text-align:right;font-weight:900;font-size:18px;font-family:monospace;">
            € {{ number_format($order->quoted_amount_excl_btw, 2, ',', '.') }}
        </td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Amount incl. 21% VAT</td>
        <td style="padding:8px 0;text-align:right;font-family:monospace;border-top:1px solid #EEE;">
            € {{ number_format($order->quoted_amount_excl_btw * 1.21, 2, ',', '.') }}
        </td></tr>
    @endif
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">New valid until</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">
            {{ $order->quote_valid_until->format('d-m-Y') }}
        </td></tr>
</table>

<p style="margin:24px 0;text-align:center;">
    <a href="{{ $acceptUrl }}"
       style="display:inline-block;background:#0A0A0A;color:#F5C518;padding:14px 28px;font-weight:900;font-size:16px;text-decoration:none;text-transform:uppercase;letter-spacing:0.05em;">
        View the quote →
    </a>
</p>

<p style="font-size:12px;color:#555;">This link is personal and unique to your quote. On the next page you will see all the details. You fill in your address and click <strong>Place order</strong>. Only then do you enter into an agreement for the amount stated above. If you do not click, you are not bound to anything.</p>

<p style="font-size:12px;color:#555;">Questions or something to change? Just reply to this email. <strong>Keep the subject unchanged</strong> so your message is added to your quote automatically.</p>

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
