<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Facture {{ $invoice->invoice_number }}</title>
<style>
    @font-face { font-family: Inter; src: url("file://{{ storage_path('fonts/Inter-Regular.ttf') }}") format("truetype"); font-weight: 400; }
    @font-face { font-family: Inter; src: url("file://{{ storage_path('fonts/Inter-Bold.ttf') }}") format("truetype"); font-weight: 700; }
    @font-face { font-family: BebasNeue; src: url("file://{{ storage_path('fonts/BebasNeue-Regular.ttf') }}") format("truetype"); font-weight: 400; }
    @page { size: A4; margin: 0; }
    body { font-family: Inter, Arial, sans-serif; color: #0A0A0A; font-size: 10pt; line-height: 1.4; margin: 0; padding: 0; }
    .brand { background: #F5C518; padding: 8mm 14mm; font-family: BebasNeue, Impact, sans-serif; font-weight: 400; font-size: 28pt; letter-spacing: 0.06em; }
    .wrap { padding: 8mm 14mm; }
    .top { width: 100%; margin-bottom: 8mm; }
    .top td { vertical-align: top; }
    .top .leverancier { width: 55%; }
    .top .doc-info { width: 45%; text-align: right; }
    .top h3 { font-family: BebasNeue, Impact, sans-serif; font-size: 11pt; font-weight: 400; text-transform: uppercase; margin-bottom: 2mm; letter-spacing: 0.08em; }
    .top .name { font-family: BebasNeue, Impact, sans-serif; font-weight: 400; font-size: 14pt; margin-bottom: 2mm; letter-spacing: 0.04em; }
    h1 { font-family: BebasNeue, Impact, sans-serif; font-size: 28pt; font-weight: 400; margin: 6mm 0 2mm; letter-spacing: 0.04em; }
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
@php
    $co = config('desnipperaar.company');
    $labelMap = [
        'Kennismaking — eerste doos' => 'Offre de bienvenue — premier carton',
        'Daarna eerste doos'         => 'Ensuite premier carton',
        'Volgende dozen'             => 'Cartons suivants',
        'Eerste doos'                => 'Premier carton',
        'Eerste rolcontainer 240 L'  => 'Premier conteneur roulant 240 L',
        'Volgende rolcontainers'     => 'Conteneurs roulants suivants',
        'Telefoon / tablet'          => 'Téléphone / tablette',
    ];
    $tr = fn ($label) => $labelMap[$label] ?? $label;
@endphp

<div class="brand">DESNIPPERAAR</div>

<div class="wrap">

    <table class="top">
        <tr>
            <td class="leverancier">
                <h3>Émetteur</h3>
                <div class="name">{{ $co['name'] }}</div>
                <div>{{ $co['address'] }}</div>
                <div>{{ $co['postcode'] }} {{ $co['city'] }}</div>
                <div>{{ $co['country'] }}</div>
                @if ($co['kvk']) <div style="margin-top:2mm;">RC&nbsp;: <strong>{{ $co['kvk'] }}</strong></div> @endif
                @if ($co['btw']) <div>TVA&nbsp;: <strong>{{ $co['btw'] }}</strong></div> @endif
                <div style="margin-top:2mm;">{{ $co['phone'] }} &middot; {{ $co['email'] }}</div>
            </td>
            <td class="doc-info">
                <h1>FACTURE</h1>
                <div class="num">{{ $invoice->invoice_number }}</div>
                <table class="dates" style="width:100%;text-align:right;">
                    <tr><td class="k">Date de facture</td><td>{{ $invoice->issued_at->format('d-m-Y') }}</td></tr>
                    <tr><td class="k">Échéance</td><td><strong>{{ $invoice->due_at->format('d-m-Y') }}</strong></td></tr>
                    <tr><td class="k">Référence commande</td><td>{{ $invoice->order->order_number }}</td></tr>
                    @if ($invoice->bon_id) <tr><td class="k">N° de bon</td><td>{{ $invoice->bon?->bon_number }}</td></tr> @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="klant">
        <h3>Destinataire</h3>
        @if ($invoice->customer_company) <div class="name">{{ $invoice->customer_company }}</div> @endif
        <div>{{ $invoice->customer_name }}</div>
        @if ($invoice->customer_address) <div>{{ $invoice->customer_address }}</div> @endif
        <div>{{ $invoice->customer_postcode }} {{ $invoice->customer_city }}</div>
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th>Désignation</th>
                <th class="r">Qté</th>
                <th class="r">Prix unit.</th>
                <th class="r">Sous-total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr>
                    <td>{{ $tr($line['label']) }}</td>
                    <td class="r">{{ $line['qty'] }}</td>
                    <td class="r">
                        € {{ number_format($line['unit'], 2, ',', '.') }}
                        @if (!empty($line['was_unit']))
                            <span style="text-decoration:line-through;color:#999;margin-left:4px;">€ {{ number_format($line['was_unit'], 2, ',', '.') }}</span>
                        @endif
                    </td>
                    <td class="r">
                        € {{ number_format($line['was_subtotal'] ?? $line['subtotal'], 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $subtotalRegular = collect($invoice->lines)->sum(fn ($l) => $l['was_subtotal'] ?? $l['subtotal']);
        $discount = round($subtotalRegular - (float) $invoice->amount_excl_btw, 2);
    @endphp
    <table class="totals">
        <tr><td class="k">{{ $discount > 0 ? 'Sous-total avant remise' : 'Sous-total' }} hors TVA</td><td class="v">€ {{ number_format($subtotalRegular, 2, ',', '.') }}</td></tr>
        @php
            $discountKennismaking = collect($invoice->lines)->sum(fn ($l) => ($l['unit'] == 0 && isset($l['was_subtotal'])) ? $l['was_subtotal'] : 0);
            $discountPilot = max(0, round($discount - $discountKennismaking, 2));
        @endphp
        @if ($discountKennismaking > 0)
            <tr><td class="k">Remise offre de bienvenue</td><td class="v">− € {{ number_format($discountKennismaking, 2, ',', '.') }}</td></tr>
        @endif
        @if ($discountPilot > 0)
            <tr><td class="k">Remise pilote Amsterdam</td><td class="v">− € {{ number_format($discountPilot, 2, ',', '.') }}</td></tr>
        @endif
        <tr><td class="k">TVA {{ number_format($invoice->vat_rate * 100, 0) }}%</td><td class="v">€ {{ number_format($invoice->vat_amount, 2, ',', '.') }}</td></tr>
        <tr class="grand"><td>Total TTC</td><td class="v">€ {{ number_format($invoice->amount_incl_btw, 2, ',', '.') }}</td></tr>
    </table>

    <div class="pay">
        <h3>Paiement</h3>
        <div class="row"><span class="k">Échéance</span><span class="v">{{ $invoice->due_at->format('d-m-Y') }} ({{ config('desnipperaar.invoice.payment_terms_days') }} jours)</span></div>
        <div class="row"><span class="k">Montant</span><span class="v">€ {{ number_format($invoice->amount_incl_btw, 2, ',', '.') }}</span></div>
        @if ($co['iban']) <div class="row"><span class="k">IBAN</span><span class="v">{{ $co['iban'] }}</span></div> @endif
        @if ($co['bic']) <div class="row"><span class="k">BIC</span><span class="v">{{ $co['bic'] }}</span></div> @endif
        <div class="row"><span class="k">Référence</span><span class="v">{{ $invoice->invoice_number }}</span></div>
    </div>
    <p class="small">Merci de régler en mentionnant le numéro de facture. Des questions&nbsp;? {{ $co['email'] }}.</p>

</div>

<div class="foot">RGPD · DIN 66399 · Personnel avec VOG · Assuré · Couverture € 2,5M</div>

</body>
</html>
