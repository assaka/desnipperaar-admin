@component('emails._layout', ['title' => 'Orderbevestiging '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Bedankt voor uw opdracht.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Hierbij de bevestiging van uw opdracht. Uw ordernummer is
<strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
We nemen binnen één werkdag contact met u op om de ophaling te bevestigen.</p>

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Besteloverzicht</h2>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:16px;">
    @foreach ($quote['lines'] as $line)
        <tr>
            <td style="padding:6px 0;color:#333;font-size:13px;border-bottom:1px dashed #DDD;">{{ $line['label'] }}</td>
            <td style="padding:6px 0;color:#666;font-size:12px;border-bottom:1px dashed #DDD;text-align:center;font-family:'Courier New',monospace;white-space:nowrap;">
                {{ $line['qty'] }} &times; € {{ number_format($line['unit'], 2, ',', '.') }}
            </td>
            <td style="padding:6px 0;font-weight:700;font-size:13px;border-bottom:1px dashed #DDD;text-align:right;font-family:'Courier New',monospace;white-space:nowrap;">
                € {{ number_format($line['subtotal'], 2, ',', '.') }}
            </td>
        </tr>
    @endforeach

    @foreach ($mediaLines as $line)
        <tr>
            <td style="padding:6px 0;color:#333;font-size:13px;border-bottom:1px dashed #DDD;">{{ $line['label'] }}</td>
            <td style="padding:6px 0;color:#666;font-size:12px;border-bottom:1px dashed #DDD;text-align:center;font-family:'Courier New',monospace;white-space:nowrap;">
                {{ $line['qty'] }} &times; € {{ number_format($line['unit'], 2, ',', '.') }}
            </td>
            <td style="padding:6px 0;font-weight:700;font-size:13px;border-bottom:1px dashed #DDD;text-align:right;font-family:'Courier New',monospace;white-space:nowrap;">
                € {{ number_format($line['subtotal'], 2, ',', '.') }}
            </td>
        </tr>
    @endforeach

    <tr>
        <td style="padding:10px 0 4px;color:#555;font-size:12px;" colspan="2">Subtotaal (excl. btw)</td>
        <td style="padding:10px 0 4px;font-family:'Courier New',monospace;text-align:right;font-size:13px;">€ {{ number_format($subtotal, 2, ',', '.') }}</td>
    </tr>
    <tr>
        <td style="padding:4px 0;color:#555;font-size:12px;" colspan="2">BTW 21%</td>
        <td style="padding:4px 0;font-family:'Courier New',monospace;text-align:right;font-size:13px;">€ {{ number_format($vat, 2, ',', '.') }}</td>
    </tr>
    <tr>
        <td style="padding:10px 0 4px;font-weight:900;font-size:15px;border-top:2px solid #0A0A0A;" colspan="2">Totaal incl. btw</td>
        <td style="padding:10px 0 4px;font-weight:900;font-size:16px;border-top:2px solid #0A0A0A;text-align:right;font-family:'Courier New',monospace;">€ {{ number_format($total, 2, ',', '.') }}</td>
    </tr>
</table>

@if ($order->pilot)
    <p style="background:#F5C518;padding:6px 10px;display:inline-block;font-size:12px;font-weight:700;margin:0 0 16px;">
        ✓ Noord-pilot · 20% korting toegepast
    </p>
@endif
@if ($order->first_box_free)
    <p style="background:#0A0A0A;color:#F5C518;padding:6px 10px;display:inline-block;font-size:12px;font-weight:700;margin:0 0 16px;">
        ✨ Kennismaking · eerste doos gratis
    </p>
@endif

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Ophaaladres</h2>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:16px;">
    <tr>
        <td style="padding:4px 0;color:#555;font-size:12px;width:140px;">Naam</td>
        <td style="padding:4px 0;font-weight:700;font-size:13px;">{{ $order->customer_name }}</td>
    </tr>
    @if ($order->customer_address)
        <tr>
            <td style="padding:4px 0;color:#555;font-size:12px;">Adres</td>
            <td style="padding:4px 0;font-weight:700;font-size:13px;">{{ $order->customer_address }}</td>
        </tr>
    @endif
    <tr>
        <td style="padding:4px 0;color:#555;font-size:12px;">Postcode / stad</td>
        <td style="padding:4px 0;font-weight:700;font-size:13px;">
            <span style="font-family:'Courier New',monospace;">{{ $order->customer_postcode }}</span>
            @if ($order->customer_city) &middot; {{ $order->customer_city }} @endif
        </td>
    </tr>
    <tr>
        <td style="padding:4px 0;color:#555;font-size:12px;">Leveringsmethode</td>
        <td style="padding:4px 0;font-weight:700;font-size:13px;">{{ ucfirst($order->delivery_mode) }}service</td>
    </tr>
    @if ($order->pickup_date)
        <tr>
            <td style="padding:4px 0;color:#555;font-size:12px;">Gewenste datum</td>
            <td style="padding:4px 0;font-weight:700;font-size:13px;">
                {{ $order->pickup_date->format('d-m-Y') }}
                @if ($order->pickup_window) ({{ $order->pickup_window }}) @endif
            </td>
        </tr>
    @endif
</table>

<p style="font-size:12px;color:#555;">Klopt het adres niet? Reply op deze mail met de juiste gegevens.</p>

<p>Heeft u vragen? Bel <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a>
of mail naar <a href="mailto:sales@desnipperaar.nl" style="color:#0A0A0A;">sales@desnipperaar.nl</a>.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
