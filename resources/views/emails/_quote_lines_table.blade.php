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
        <td style="padding:6px 0;">{{ $line['label'] }}@if (!empty($line['optional'])) <span style="color:#888;font-size:11px;">({{ $labels['optional'] ?? 'optioneel' }})</span>@endif</td>
        <td align="right" style="padding:6px 8px;font-family:monospace;">{{ 0.0 == fmod((float) $line['qty'], 1) ? number_format($line['qty'], 0, ',', '.') : number_format($line['qty'], 2, ',', '.') }}</td>
        <td align="right" style="padding:6px 8px;font-family:monospace;">€ {{ number_format($line['unit'], 2, ',', '.') }}</td>
        <td align="right" style="padding:6px 0;font-family:monospace;">{{ !empty($line['optional']) ? '+ ' : '' }}€ {{ number_format($line['subtotal'], 2, ',', '.') }}</td>
    </tr>
    @endforeach
</table>
@if (collect($order->quote_lines)->contains(fn ($l) => !empty($l['optional'])))
<p style="font-size:12px;color:#555;margin-top:-6px;">{{ $labels['optional_note'] ?? 'Regels gemarkeerd als optioneel kunt u zelf aan- of uitzetten op de offertepagina. Het totaal past zich dan aan.' }}</p>
@endif
@endif
