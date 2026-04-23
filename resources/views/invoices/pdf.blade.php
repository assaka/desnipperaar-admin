<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>Factuur {{ $invoice->invoice_number }}</title>
<style>
    @page { size: A4; margin: 0; }
    body { font-family: Arial, Helvetica, sans-serif; color: #0A0A0A; font-size: 10pt; line-height: 1.4; margin: 0; padding: 0; }
    .brand { background: #F5C518; padding: 8mm 14mm; font-weight: 900; font-size: 20pt; letter-spacing: 0.04em; }
    .wrap { padding: 8mm 14mm; }
    .top { width: 100%; margin-bottom: 8mm; }
    .top td { vertical-align: top; }
    .top .leverancier { width: 55%; }
    .top .doc-info { width: 45%; text-align: right; }
    .top h3 { font-size: 10pt; font-weight: 900; text-transform: uppercase; margin-bottom: 2mm; letter-spacing: 0.04em; }
    .top .name { font-weight: 900; font-size: 12pt; margin-bottom: 2mm; }
    h1 { font-size: 24pt; font-weight: 900; margin: 6mm 0 2mm; }
    .num { font-family: 'Courier New', monospace; font-size: 14pt; background: #F5C518; padding: 2mm 4mm; display: inline-block; margin-bottom: 6mm; }
    .dates { margin-bottom: 6mm; font-size: 10pt; }
    .dates td { padding: 1mm 8mm 1mm 0; }
    .dates .k { color: #555; }
    .klant { margin-bottom: 8mm; padding: 4mm; background: #F7F7F4; border-left: 3px solid #F5C518; }
    .klant h3 { font-size: 9pt; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2mm; color: #555; }
    .klant .name { font-weight: 700; font-size: 11pt; }
    table.lines { width: 100%; border-collapse: collapse; margin: 4mm 0; }
    table.lines th { background: #0A0A0A; color: #F5C518; padding: 2mm 3mm; text-align: left; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.04em; }
    table.lines th.r { text-align: right; }
    table.lines td { padding: 2mm 3mm; border-bottom: 1px solid #DDD; font-size: 10pt; }
    table.lines td.r { text-align: right; font-family: 'Courier New', monospace; white-space: nowrap; }
    .totals { width: 60mm; margin-left: auto; margin-top: 4mm; }
    .totals td { padding: 1mm 2mm; font-size: 10pt; }
    .totals .k { color: #555; }
    .totals .v { text-align: right; font-family: 'Courier New', monospace; white-space: nowrap; }
    .totals .grand td { font-weight: 900; font-size: 12pt; border-top: 2px solid #0A0A0A; padding-top: 2mm; }
    .pay { margin-top: 8mm; padding: 4mm 5mm; border: 2px solid #0A0A0A; page-break-inside: avoid; }
    .pay h3 { font-size: 10pt; font-weight: 900; text-transform: uppercase; margin-bottom: 2mm; letter-spacing: 0.04em; }
    .pay .row { margin-bottom: 1mm; font-size: 9.5pt; }
    .pay .k { display: inline-block; width: 28mm; color: #555; font-size: 9pt; }
    .pay .v { font-weight: 700; font-family: 'Courier New', monospace; }
    .small { font-size: 8pt; color: #555; margin-top: 4mm; }
    .foot { background: #F7F7F4; padding: 4mm 14mm; font-family: 'Courier New', monospace; font-size: 8pt; letter-spacing: 0.1em; color: #555; text-align: center; margin-top: 6mm; border-top: 1px solid #DDD; page-break-inside: avoid; }
</style>
</head>
<body>
@php $co = config('desnipperaar.company'); @endphp

<div class="brand">DESNIPPERAAR</div>

<div class="wrap">

    <table class="top">
        <tr>
            <td class="leverancier">
                <h3>Afzender</h3>
                <div class="name">{{ $co['name'] }}</div>
                <div>{{ $co['address'] }}</div>
                <div>{{ $co['postcode'] }} {{ $co['city'] }}</div>
                <div>{{ $co['country'] }}</div>
                @if ($co['kvk']) <div style="margin-top:2mm;">KvK: <strong>{{ $co['kvk'] }}</strong></div> @endif
                @if ($co['btw']) <div>BTW: <strong>{{ $co['btw'] }}</strong></div> @endif
                <div style="margin-top:2mm;">{{ $co['phone'] }} &middot; {{ $co['email'] }}</div>
            </td>
            <td class="doc-info">
                <h1>FACTUUR</h1>
                <div class="num">{{ $invoice->invoice_number }}</div>
                <table class="dates" style="width:100%;text-align:right;">
                    <tr><td class="k">Factuurdatum</td><td>{{ $invoice->issued_at->format('d-m-Y') }}</td></tr>
                    <tr><td class="k">Vervaldatum</td><td><strong>{{ $invoice->due_at->format('d-m-Y') }}</strong></td></tr>
                    <tr><td class="k">Orderreferentie</td><td>{{ $invoice->order->order_number }}</td></tr>
                    @if ($invoice->bon_id) <tr><td class="k">Bonnummer</td><td>{{ $invoice->bon?->bon_number }}</td></tr> @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="klant">
        <h3>Aan</h3>
        @if ($invoice->customer_company) <div class="name">{{ $invoice->customer_company }}</div> @endif
        <div>{{ $invoice->customer_name }}</div>
        @if ($invoice->customer_address) <div>{{ $invoice->customer_address }}</div> @endif
        <div>{{ $invoice->customer_postcode }} {{ $invoice->customer_city }}</div>
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th>Omschrijving</th>
                <th class="r">Aantal</th>
                <th class="r">Eenheidsprijs</th>
                <th class="r">Subtotaal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr>
                    <td>{{ $line['label'] }}</td>
                    <td class="r">{{ $line['qty'] }}</td>
                    <td class="r">€ {{ number_format($line['unit'], 2, ',', '.') }}</td>
                    <td class="r">€ {{ number_format($line['subtotal'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $subtotalRegular = collect($invoice->lines)->sum(fn ($l) => $l['was_subtotal'] ?? $l['subtotal']);
        $discount = round($subtotalRegular - (float) $invoice->amount_excl_btw, 2);
    @endphp
    <table class="totals">
        <tr><td class="k">Subtotaal excl. btw</td><td class="v">€ {{ number_format($subtotalRegular, 2, ',', '.') }}</td></tr>
        @if ($discount > 0)
            <tr><td class="k">Korting Noord-pilot</td><td class="v">− € {{ number_format($discount, 2, ',', '.') }}</td></tr>
        @endif
        <tr><td class="k">BTW {{ number_format($invoice->vat_rate * 100, 0) }}%</td><td class="v">€ {{ number_format($invoice->vat_amount, 2, ',', '.') }}</td></tr>
        <tr class="grand"><td>Totaal incl. btw</td><td class="v">€ {{ number_format($invoice->amount_incl_btw, 2, ',', '.') }}</td></tr>
    </table>

    <div class="pay">
        <h3>Betaling</h3>
        <div class="row"><span class="k">Vervaldatum</span><span class="v">{{ $invoice->due_at->format('d-m-Y') }} ({{ config('desnipperaar.invoice.payment_terms_days') }} dagen)</span></div>
        <div class="row"><span class="k">Bedrag</span><span class="v">€ {{ number_format($invoice->amount_incl_btw, 2, ',', '.') }}</span></div>
        @if ($co['iban']) <div class="row"><span class="k">IBAN</span><span class="v">{{ $co['iban'] }}</span></div> @endif
        @if ($co['bic']) <div class="row"><span class="k">BIC</span><span class="v">{{ $co['bic'] }}</span></div> @endif
        <div class="row"><span class="k">Kenmerk</span><span class="v">{{ $invoice->invoice_number }}</span></div>
    </div>
    <p class="small">Gelieve onder vermelding van het factuurnummer over te maken. Bij vragen: {{ $co['email'] }}.</p>

</div>

<div class="foot">AVG · DIN 66399 · VOG · Verzekerd · € 2,5 mln dekking</div>

</body>
</html>
