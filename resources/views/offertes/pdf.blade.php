<!DOCTYPE html>
<html lang="{{ $lang }}">
@php
    // Eén sjabloon voor NL en EN. Een offerte op briefpapier vragen vooral
    // buitenlandse inkoopafdelingen, die Engels willen ongeacht de taal van de
    // order, dus de taal komt van de knop en niet van $order->locale.
    $t = [
        'nl' => [
            'title' => 'Offerte', 'brand' => 'OFFERTE', 'from' => 'Van', 'to' => 'Aan', 'country' => 'Nederland',
            'coc' => 'KvK', 'vat' => 'Btw', 'attn' => 't.a.v.',
            'number' => 'Offertenummer', 'date' => 'Offertedatum', 'valid' => 'Geldig tot', 'customer' => 'Klantnummer',
            'desc' => 'Omschrijving', 'qty' => 'Aantal', 'unit' => 'Prijs', 'subtotal' => 'Subtotaal',
            'optional' => 'optioneel', 'optional_note' => 'Regels met + zijn optioneel en niet in het totaal opgenomen.',
            'sub_excl' => 'Subtotaal excl. btw', 'vat_line' => 'Btw 21%', 'total' => 'Totaal incl. btw',
            'terms' => 'Toelichting', 'certificate' => 'Elke vernietiging wordt bevestigd met een vernietigingscertificaat.',
            'payment' => 'Betaling', 'term' => 'Termijn', 'term_v' => ':days dagen na factuurdatum', 'reference' => 'Kenmerk',
            'for' => 'Namens', 'small' => 'Alle bedragen in euro. Vermeld bij uw inkooporder dit offertenummer, dan nemen wij het ordernummer over op de factuur.',
        ],
        'en' => [
            'title' => 'Quotation', 'brand' => 'QUOTATION', 'from' => 'From', 'to' => 'To', 'country' => 'The Netherlands',
            'coc' => 'CoC', 'vat' => 'VAT', 'attn' => 'Attn.',
            'number' => 'Quotation number', 'date' => 'Quotation date', 'valid' => 'Valid until', 'customer' => 'Customer number',
            'desc' => 'Description', 'qty' => 'Qty', 'unit' => 'Unit price', 'subtotal' => 'Subtotal',
            'optional' => 'optional', 'optional_note' => 'Lines marked + are optional and not included in the total.',
            'sub_excl' => 'Subtotal excl. VAT', 'vat_line' => 'VAT 21%', 'total' => 'Total incl. VAT',
            'terms' => 'Terms of this quotation', 'certificate' => 'Every destruction is confirmed with a certificate of destruction.',
            'payment' => 'Payment', 'term' => 'Term', 'term_v' => ':days days after invoice', 'reference' => 'Reference',
            'for' => 'For', 'small' => 'All prices in euros. Please state this quotation number on your purchase order so we can put your PO number on the invoice.',
        ],
    ][$lang];

    $co    = config('desnipperaar.company');
    $money = fn ($v) => '€ '.number_format($v, 2, ',', '.');
    $qty   = fn ($q) => 0.0 == fmod((float) $q, 1) ? number_format($q, 0, ',', '.') : number_format($q, 2, ',', '.');
    $excl  = (float) $order->quoted_amount_excl_btw;
    $vat   = round($excl * \App\Support\Pricing::VAT_RATE, 2);
    $notes = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $order->quote_body))));
    $lines = $order->quote_lines ?? [];
    $hasOptional = collect($lines)->contains(fn ($l) => !empty($l['optional']));
    $company = $order->customer?->company;
    $sender  = $order->senderUser()?->name;
@endphp
<head>
<meta charset="UTF-8">
<title>{{ $t['title'] }} {{ $order->order_number }}</title>
<style>
    @font-face { font-family: Inter; src: url("file://{{ storage_path('fonts/Inter-Regular.ttf') }}") format("truetype"); font-weight: 400; }
    @font-face { font-family: Inter; src: url("file://{{ storage_path('fonts/Inter-Bold.ttf') }}") format("truetype"); font-weight: 700; }
    @font-face { font-family: BebasNeue; src: url("file://{{ storage_path('fonts/BebasNeue-Regular.ttf') }}") format("truetype"); font-weight: 400; }
    @page { size: A4; margin: 13mm 0 10mm 0; }
    body { font-family: Inter, Arial, sans-serif; color: #0A0A0A; font-size: 9.5pt; line-height: 1.35; margin: 0; padding: 0; }
    .brand { position: fixed; top: -13mm; left: 0; right: 0; background: #F5C518; padding: 2.5mm 14mm; line-height: 1; font-family: BebasNeue, Impact, sans-serif; font-size: 16pt; letter-spacing: 0.06em; }
    .wrap { padding: 4mm 14mm; }
    .top { width: 100%; margin-bottom: 5mm; }
    .top td { vertical-align: top; }
    .top .leverancier { width: 32%; padding-right: 6mm; }
    .top .ontvanger { width: 30%; padding-right: 6mm; }
    .top .doc-info { width: 38%; text-align: right; }
    .top h3 { font-size: 9pt; font-weight: 700; text-transform: uppercase; margin-bottom: 2mm; letter-spacing: 0.08em; color: #555; }
    .top .name { font-weight: 700; font-size: 12pt; margin-bottom: 2mm; }
    .dates td { padding: 0.8mm 0; }
    .dates .k { color: #555; padding-right: 5mm; white-space: nowrap; }
    table.lines { width: 100%; border-collapse: collapse; margin: 3mm 0 2mm; }
    table.lines th { background: #0A0A0A; color: #F5C518; padding: 1.2mm 3mm; text-align: left; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.04em; }
    table.lines th.r { text-align: right; }
    table.lines td { padding: 0.9mm 3mm; border-bottom: 1px solid #DDD; font-size: 9.5pt; }
    table.lines td.r { text-align: right; font-family: 'Courier New', monospace; white-space: nowrap; }
    .totals { width: 110mm; margin-left: auto; margin-top: 4mm; }
    .totals td { padding: 0.3mm 2mm; font-size: 9.5pt; line-height: 1.2; }
    .totals .k { color: #555; }
    .totals .v { text-align: right; font-family: 'Courier New', monospace; white-space: nowrap; width: 30mm; }
    .totals .grand td { font-weight: 700; font-size: 12pt; border-top: 2px solid #0A0A0A; padding-top: 2mm; }
    .box { padding: 2.5mm 5mm; border: 2px solid #0A0A0A; page-break-inside: avoid; }
    .box h3 { font-size: 9pt; font-weight: 700; text-transform: uppercase; margin-bottom: 1.2mm; letter-spacing: 0.04em; }
    .box .row { margin-bottom: 0.4mm; }
    .box .k { display: inline-block; width: 18mm; color: #555; font-size: 9pt; }
    .box .v { font-weight: 700; }
    ul { margin: 0; padding-left: 5mm; }
    li { margin-bottom: 0.8mm; }
    .small { font-size: 8pt; color: #555; margin-top: 3mm; }
    .foot { position: fixed; bottom: -10mm; left: 0; right: 0; background: #F7F7F4; padding: 2.5mm 14mm; font-family: 'Courier New', monospace; font-size: 8pt; letter-spacing: 0.1em; color: #555; text-align: center; border-top: 1px solid #DDD; }
</style>
</head>
<body>

<div class="brand">
    <table style="width:100%;"><tr>
        <td>DESNIPPERAAR</td>
        <td style="text-align:right;">{{ $t['brand'] }}</td>
    </tr></table>
</div>

<div class="wrap">

    <table class="top">
        <tr>
            <td class="leverancier">
                <h3>{{ $t['from'] }}</h3>
                <div class="name">{{ $co['name'] }}</div>
                <div>{{ $co['address'] }}</div>
                <div>{{ $co['postcode'] }} {{ $co['city'] }}</div>
                <div>{{ $t['country'] }}</div>
                @if ($co['kvk']) <div style="margin-top:2mm;">{{ $t['coc'] }}: <strong>{{ $co['kvk'] }}</strong></div> @endif
                @if ($co['btw']) <div>{{ $t['vat'] }}: <strong>{{ $co['btw'] }}</strong></div> @endif
                <div style="margin-top:2mm;">{{ $co['email'] }}</div>
                <div>{{ $co['phone'] }}</div>
                <div>{{ $co['website'] }}</div>
            </td>
            <td class="ontvanger">
                <h3>{{ $t['to'] }}</h3>
                @if ($company)
                    <div class="name">{{ $company }}</div>
                    <div>{{ $t['attn'] }} {{ $order->customer_name }}</div>
                @else
                    <div class="name">{{ $order->customer_name }}</div>
                @endif
                @if ($order->customer_address) <div>{{ $order->customer_address }}</div> @endif
                <div>{{ $order->customer_postcode }} {{ $order->customer_city }}</div>
            </td>
            <td class="doc-info">
                <table class="dates" style="width:100%;text-align:right;">
                    <tr><td class="k">{{ $t['number'] }}</td><td><strong>{{ $order->order_number }}</strong></td></tr>
                    <tr><td class="k">{{ $t['date'] }}</td><td>{{ ($order->quote_sent_at ?? now())->format('d-m-Y') }}</td></tr>
                    @if ($order->quote_valid_until)
                        <tr><td class="k">{{ $t['valid'] }}</td><td><strong>{{ $order->quote_valid_until->format('d-m-Y') }}</strong></td></tr>
                    @endif
                    @if ($order->customer_reference)
                        <tr><td class="k">{{ $t['customer'] }}</td><td>{{ $order->customer_reference }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>{{ $t['desc'] }}</th>
                <th class="r">{{ $t['qty'] }}</th>
                <th class="r">{{ $t['unit'] }}</th>
                <th class="r">{{ $t['subtotal'] }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>{{ $line['label'] }}@if (!empty($line['optional'])) <span style="color:#888;font-size:8.5pt;">({{ $t['optional'] }})</span>@endif</td>
                    <td class="r">{{ $qty($line['qty']) }}</td>
                    <td class="r">{{ $money($line['unit']) }}</td>
                    <td class="r">{{ !empty($line['optional']) ? '+ ' : '' }}{{ $money($line['subtotal']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if ($hasOptional)
        <p style="font-size:8pt;color:#777;margin:1mm 0 0;">{{ $t['optional_note'] }}</p>
    @endif

    <table class="totals">
        <tr><td class="k">{{ $t['sub_excl'] }}</td><td class="v">{{ $money($excl) }}</td></tr>
        <tr><td class="k">{{ $t['vat_line'] }}</td><td class="v">{{ $money($vat) }}</td></tr>
        <tr class="grand"><td>{{ $t['total'] }}</td><td class="v">{{ $money($excl + $vat) }}</td></tr>
    </table>

    <table style="width:100%;margin-top:4mm;border-collapse:separate;border-spacing:0;">
    <tr>
        <td style="width:58%;vertical-align:top;padding-right:3mm;">
            <div class="box">
                <h3>{{ $t['terms'] }}</h3>
                <ul>
                    @foreach ($notes as $n) <li>{{ $n }}</li> @endforeach
                    <li>{{ $t['certificate'] }}</li>
                </ul>
            </div>
        </td>
        <td style="width:42%;vertical-align:top;">
            <div class="box">
                <h3>{{ $t['payment'] }}</h3>
                <div class="row"><span class="k">{{ $t['term'] }}</span><span class="v">{{ str_replace(':days', config('desnipperaar.invoice.payment_terms_days'), $t['term_v']) }}</span></div>
                @if ($co['iban']) <div class="row"><span class="k">IBAN</span><span class="v">{{ $co['iban'] }}</span></div> @endif
                @if ($co['bic']) <div class="row"><span class="k">BIC</span><span class="v">{{ $co['bic'] }}</span></div> @endif
                <div class="row"><span class="k">{{ $t['reference'] }}</span><span class="v">{{ $order->order_number }}</span></div>
            </div>
            <div style="margin-top:9mm;border-bottom:1px solid #0A0A0A;"></div>
            <div style="font-size:9pt;margin-top:1.5mm;">{{ $t['for'] }} {{ $co['name'] }}@if ($sender), {{ $sender }}@endif</div>
        </td>
    </tr>
    </table>

    <p class="small">{{ $t['small'] }}</p>

</div>

<div class="foot">{{ $lang === 'en' ? 'GDPR · DIN 66399 · VOG-screened · Insured · € 2.5M coverage' : 'AVG · DIN 66399 · VOG · Verzekerd · € 2,5 mln dekking' }}</div>

</body>
</html>
