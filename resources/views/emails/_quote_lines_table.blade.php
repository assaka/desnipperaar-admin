@if (!empty($order->quote_lines))
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;font-size:13px;border-collapse:collapse;">
    <tr style="border-bottom:2px solid #0A0A0A;">
        <th align="left"  style="padding:6px 0;">{{ $labels['desc'] }}</th>
        <th align="right" style="padding:6px 8px;">{{ $labels['qty'] }}</th>
        <th align="right" style="padding:6px 8px;">{{ $labels['price'] }}</th>
        <th align="right" style="padding:6px 0;">{{ $labels['subtotal'] }}</th>
    </tr>
    @foreach ($order->quote_lines as $line)
    <tr style="border-bottom:1px solid #EEE;">
        <td style="padding:6px 0;">{{ $line['label'] }}</td>
        <td align="right" style="padding:6px 8px;font-family:monospace;">{{ 0.0 == fmod((float) $line['qty'], 1) ? number_format($line['qty'], 0, ',', '.') : number_format($line['qty'], 2, ',', '.') }}</td>
        <td align="right" style="padding:6px 8px;font-family:monospace;">€ {{ number_format($line['unit'], 2, ',', '.') }}</td>
        <td align="right" style="padding:6px 0;font-family:monospace;">€ {{ number_format($line['subtotal'], 2, ',', '.') }}</td>
    </tr>
    @endforeach
</table>
@endif
