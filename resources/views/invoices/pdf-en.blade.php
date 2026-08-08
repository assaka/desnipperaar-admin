<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $invoice->isCreditNote() ? "Credit note " : "Invoice " }}{{ $invoice->invoice_number }}</title>
<style>
    @font-face { font-family: Inter; src: url("file://{{ storage_path('fonts/Inter-Regular.ttf') }}") format("truetype"); font-weight: 400; }
    @font-face { font-family: Inter; src: url("file://{{ storage_path('fonts/Inter-Bold.ttf') }}") format("truetype"); font-weight: 700; }
    @font-face { font-family: BebasNeue; src: url("file://{{ storage_path('fonts/BebasNeue-Regular.ttf') }}") format("truetype"); font-weight: 400; }
    @page { size: A4; margin: 13mm 0 10mm 0; }
    body { font-family: Inter, Arial, sans-serif; color: #0A0A0A; font-size: 9.5pt; line-height: 1.35; margin: 0; padding: 0; }
    .brand { position: fixed; top: -13mm; left: 0; right: 0; background: #F5C518; padding: 2.5mm 14mm; line-height: 1; font-family: BebasNeue, Impact, sans-serif; font-weight: 400; font-size: 16pt; letter-spacing: 0.06em; }
    .wrap { padding: 4mm 14mm; }
    .top { width: 100%; margin-bottom: 5mm; }
    .top td { vertical-align: top; }
    .top .leverancier { width: 32%; padding-right: 6mm; }
    .top .ontvanger { width: 30%; padding-right: 6mm; }
    .top .doc-info { width: 38%; text-align: right; }
    .top h3 { font-size: 9pt; font-weight: 700; text-transform: uppercase; margin-bottom: 2mm; letter-spacing: 0.08em; color: #555; }
    .top .name { font-weight: 700; font-size: 12pt; margin-bottom: 2mm; }
    h1 { font-family: BebasNeue, Impact, sans-serif; font-size: 26pt; font-weight: 400; margin: 6mm 0 2mm; letter-spacing: 0.04em; }
    .num { font-family: 'Courier New', monospace; font-size: 12pt; background: #F5C518; padding: 2mm 4mm; display: inline-block; margin-bottom: 6mm; white-space: nowrap; }
    .dates { margin-bottom: 6mm; font-size: 9.5pt; }
    .dates td { padding: 0.8mm 0; }
    .dates .k { color: #555; padding-right: 5mm; white-space: nowrap; }
    .klant { margin-top: 0; }
    .klant h3 { font-size: 9pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2mm; color: #555; }
    .klant .name { font-weight: 700; font-size: 12pt; }
    table.lines { width: 100%; border-collapse: collapse; margin: 3mm 0 2mm; }
    table.lines th { background: #0A0A0A; color: #F5C518; padding: 1.2mm 3mm; text-align: left; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.04em; }
    table.lines th.r { text-align: right; }
    table.lines td { padding: 0.9mm 3mm; border-bottom: 1px solid #DDD; font-size: 9.5pt; }
    table.lines td.r { text-align: right; font-family: 'Courier New', monospace; white-space: nowrap; }
    .totals { width: 110mm; margin-left: auto; margin-top: 4mm; }
    .totals td { padding: 0.3mm 2mm; font-size: 9.5pt; line-height: 1.2; }
    .totals .k { color: #555; }
    .totals .v { text-align: right; font-family: 'Courier New', monospace; white-space: nowrap; width: 30mm; }
    .totals .grand td { font-weight: 900; font-size: 12pt; border-top: 2px solid #0A0A0A; padding-top: 2mm; }
    .pay { margin-top: 4mm; padding: 2.5mm 5mm; border: 2px solid #0A0A0A; page-break-inside: avoid; }
    .pay h3 { font-size: 9pt; font-weight: 900; text-transform: uppercase; margin-bottom: 1.2mm; letter-spacing: 0.04em; }
    .pay .row { margin-bottom: 0.4mm; font-size: 9.5pt; }
    .pay .k { display: inline-block; width: 28mm; color: #555; font-size: 9pt; }
    .pay .v { font-weight: 700; font-family: 'Courier New', monospace; }
    .small { font-size: 8pt; color: #555; margin-top: 2mm; }
    .foot { position: fixed; bottom: -10mm; left: 0; right: 0; background: #F7F7F4; padding: 2.5mm 14mm; font-family: 'Courier New', monospace; font-size: 8pt; letter-spacing: 0.1em; color: #555; text-align: center; border-top: 1px solid #DDD; }
</style>
</head>
<body>
@php
    $co = config('desnipperaar.company');
    $labelMap = [
        'Kennismaking — eerste doos' => 'Welcome offer — first box',
        'Daarna eerste doos'         => 'Then first box',
        'Volgende dozen'             => 'Additional boxes',
        'Eerste doos'                => 'First box',
        'Eerste rolcontainer 240 L'  => 'First 240 L roll container',
        'Volgende rolcontainers'     => 'Additional roll containers',
        'Telefoon / tablet'          => 'Phone / tablet',
    ];
    $tr = fn ($label) => $labelMap[$label] ?? $label;
@endphp

<div class="brand">
    <table style="width:100%;"><tr>
        <td>DESNIPPERAAR</td>
        <td style="text-align:right;">{{ $invoice->isCreditNote() ? "CREDIT NOTE" : "INVOICE" }}</td>
    </tr></table>
</div>

<div class="wrap">

    <table class="top">
        <tr>
            <td class="leverancier">
                <h3>From</h3>
                <div class="name">{{ $co['name'] }}</div>
                <div>{{ $co['address'] }}</div>
                <div>{{ $co['postcode'] }} {{ $co['city'] }}</div>
                <div>{{ $co['country'] }}</div>
                @if ($co['kvk']) <div style="margin-top:2mm;">CoC: <strong>{{ $co['kvk'] }}</strong></div> @endif
                @if ($co['btw']) <div>VAT: <strong>{{ $co['btw'] }}</strong></div> @endif
            </td>
            <td class="ontvanger">
        <div class="klant">
            <h3>To</h3>
            @if ($invoice->customer_company) <div class="name">{{ $invoice->customer_company }}</div> @endif
            <div>{{ $invoice->customer_name }}</div>
            @if ($invoice->customer_address) <div>{{ $invoice->customer_address }}</div> @endif
            <div>{{ $invoice->customer_postcode }} {{ $invoice->customer_city }}</div>
        </div>
            </td>
            <td class="doc-info">
                <table class="dates" style="width:100%;text-align:right;">
                    <tr><td class="k">{{ $invoice->isCreditNote() ? "Credit note number" : "Invoice number" }}</td><td><strong>{{ $invoice->invoice_number }}</strong></td></tr>
                    <tr><td class="k">Invoice date</td><td>{{ $invoice->issued_at->format('d-m-Y') }}</td></tr>
                    <tr><td class="k">Due date</td><td><strong>{{ $invoice->due_at->format('d-m-Y') }}</strong></td></tr>
                    <tr><td class="k">{{ $invoice->order->isAbonnement() ? "Subscription" : "Order reference" }}</td><td>{{ $invoice->order->order_number }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>Description</th>
                <th class="r">Qty</th>
                <th class="r">Unit price</th>
                <th class="r">Subtotal</th>
            </tr>
        </thead>
    @php
        // A staffel is a per-unit reduction on data carriers, so it belongs in the
        // line price itself (flagged with an asterisk to one footnote) rather than
        // in a discount row. Kennismaking (unit 0) and the pilot keep their rows,
        // so both must be excluded from each other's total.
        $isStaffel = fn ($l) => \App\Support\Pricing::isMediaLine($l) && isset($l['was_subtotal']);

        // The coupon is stored as a line, but belongs under the subtotal with the
        // other discounts, the same as the price overview on the order. So it is
        // filtered out of the lines here and totalled separately.
        $isCoupon = fn ($l) => \App\Support\Pricing::isCouponLine($l);
        $couponLine = collect($invoice->lines)->first($isCoupon);
        $couponAmount = $couponLine ? round(abs((float) $couponLine['subtotal']), 2) : 0.0;
        $regels = collect($invoice->lines)->reject($isCoupon)->values();

        // A credit note has every line negative, so the sign is still needed.
        $money = fn ($v) => ($v < 0 ? '- € ' : '€ ').number_format(abs($v), 2, ',', '.');

        $subtotalRegular = $regels->sum(fn ($l) => $l['was_subtotal'] ?? $l['subtotal']);
        // amount_excl_btw is already net of the coupon, so add it back to work out the
        // remaining discounts. Without that the coupon lands on the Amsterdam pilot,
        // because that row is the remainder.
        $discount = round($subtotalRegular - $couponAmount - (float) $invoice->amount_excl_btw, 2);
        $discountStaffel = round($regels->sum(fn ($l) => $isStaffel($l) ? $l['was_subtotal'] - $l['subtotal'] : 0), 2);
        $discountKennismaking = $regels->sum(fn ($l) => ($l['unit'] == 0 && isset($l['was_subtotal'])) ? $l['was_subtotal'] : 0);
        $discountPilot = max(0, round($discount - $discountKennismaking - $discountStaffel, 2));
    @endphp
        <tbody>
            @foreach ($regels as $line)
                <tr>
                    <td>{{ $tr($line['label']) }}@if ($isStaffel($line))<span style="color:#2E7D32;font-weight:700;">&nbsp;*</span>@endif</td>
                    <td class="r">{{ $line['qty'] }}</td>
                    <td class="r">
                        {{ $money($line['unit']) }}
                        @if (!empty($line['was_unit']))
                            <span style="text-decoration:line-through;color:#999;margin-left:4px;">€ {{ number_format($line['was_unit'], 2, ',', '.') }}</span>
                        @endif
                    </td>
                    <td class="r">
                        {{ $money($isStaffel($line) ? $line['subtotal'] : ($line['was_subtotal'] ?? $line['subtotal'])) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="k">{{ (($discountKennismaking + $discountPilot) > 0) ? 'Subtotal before discount' : 'Subtotal' }} excl. VAT</td><td class="v">€ {{ number_format($subtotalRegular - $discountStaffel, 2, ',', '.') }}</td></tr>
        @if ($discountKennismaking > 0)
            <tr><td class="k">Welcome offer discount</td><td class="v">- € {{ number_format($discountKennismaking, 2, ',', '.') }}</td></tr>
        @endif
        @if ($discountPilot > 0)
            <tr><td class="k">Amsterdam pilot discount</td><td class="v">- € {{ number_format($discountPilot, 2, ',', '.') }}</td></tr>
        @endif
        @if ($couponAmount > 0)
            <tr><td class="k">Discount code {{ $couponLine['code'] ?? '' }}@if (!empty($couponLine['pct'])) <span style="color:#777;font-size:9pt;white-space:nowrap;">({{ \App\Support\Pricing::formatPercentage($couponLine['pct']) }}% × € {{ number_format($couponLine['base'], 2, ',', '.') }})</span>@endif</td><td class="v">- € {{ number_format($couponAmount, 2, ',', '.') }}</td></tr>
        @endif
        <tr><td class="k">VAT {{ number_format($invoice->vat_rate * 100, 0) }}%</td><td class="v">€ {{ number_format($invoice->vat_amount, 2, ',', '.') }}</td></tr>
        <tr class="grand"><td>Total incl. VAT</td><td class="v">€ {{ number_format($invoice->amount_incl_btw, 2, ',', '.') }}</td></tr>
    </table>
    @if ($discountStaffel > 0)
        <p style="font-size:9px;color:#777;margin:4px 0 0;">* Volume discount applied and already included in these prices.</p>
    @endif

    <div class="pay">
        <h3>Payment</h3>
        <div class="row"><span class="k">Amount</span><span class="v">€ {{ number_format($invoice->amount_incl_btw, 2, ',', '.') }}</span></div>
        @if ($co['iban']) <div class="row"><span class="k">IBAN</span><span class="v">{{ $co['iban'] }}</span></div> @endif
        @if ($co['bic']) <div class="row"><span class="k">BIC</span><span class="v">{{ $co['bic'] }}</span></div> @endif
        <div class="row"><span class="k">Reference</span><span class="v">{{ $invoice->invoice_number }}</span></div>
    </div>
    @if ($discountStaffel > 0)
        <p style="font-size:9px;color:#777;margin:4px 0 0;">* Volume discount applied and already included in these prices.</p>
    @endif
    <p class="small">Please pay quoting the invoice number. Questions? {{ $co['email'] }} or {{ $co['phone'] }}.</p>

</div>

<div class="foot">GDPR · DIN 66399 · VOG-screened · Insured · € 2.5M coverage</div>

</body>
</html>
