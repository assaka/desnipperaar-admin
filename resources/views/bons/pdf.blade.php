<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>{{ $bon->bon_number }} — Bon</title>
<style>
    @page { size: A4; margin: 0; }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color:#0A0A0A; background:#FFF; }
    .sheet { width:210mm; min-height:297mm; }
    .brand { background:#F5C518; padding:8mm 14mm; font-family:'Arial Black',Arial,sans-serif; font-weight:900; font-size:22pt; letter-spacing:0.04em; }
    .hero { padding:10mm 14mm; }
    .num { font-family:'Courier New',monospace; font-size:13pt; background:#F5C518; display:inline-block; padding:3px 8px; }
    .meta { padding:6mm 14mm; display:grid; grid-template-columns:1fr 1fr; gap:10mm; border-top:1px solid #E5E5E5; border-bottom:1px solid #E5E5E5; }
    .meta h3 { font-size:10pt; font-weight:900; margin-bottom:4px; text-transform:uppercase; }
    .row .k { display:inline-block; width:40%; color:#555; font-size:9pt; }
    .row .v { font-weight:700; }
    .content { padding:8mm 14mm; }
    .content h3 { font-size:11pt; font-weight:900; text-transform:uppercase; margin-bottom:5mm; }
    .seals { font-family:'Courier New',monospace; font-size:9pt; line-height:1.7; background:#FAFAFA; padding:5mm; border-left:3px solid #F5C518; }
    .signoff { padding:8mm 14mm; display:grid; grid-template-columns:1fr 1fr; gap:10mm; border-top:1px solid #E5E5E5; }
    .signoff .box { border-bottom:1px solid #000; padding:12mm 0 2mm; }
    .signoff .lbl { font-size:8pt; color:#555; text-transform:uppercase; }
    .toolbar { padding:6mm 14mm; text-align:right; }
    .toolbar button { background:#0A0A0A; color:#F5C518; font-weight:700; border:0; padding:8px 16px; cursor:pointer; }
    @media print { .toolbar { display:none; } }
</style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">Printen / opslaan als PDF</button></div>

<div class="sheet">
    <header class="brand">DESNIPPERAAR</header>

    <section class="hero">
        <div style="font-family:'Courier New',monospace;font-size:9pt;letter-spacing:0.14em;text-transform:uppercase;color:#555;margin-bottom:4px;">
            {{ ucfirst($bon->mode) }}bon
        </div>
        <h1 style="font-weight:900;font-size:22pt;margin-bottom:6px;">Afhaalbewijs</h1>
        <div class="num">{{ $bon->bon_number }}</div>
    </section>

    <section class="meta">
        <div>
            <h3>Klant</h3>
            <div class="row"><span class="k">Naam</span><span class="v">{{ $bon->order->customer_name }}</span></div>
            <div class="row"><span class="k">Adres</span><span class="v">{{ $bon->order->customer_address }}, {{ $bon->order->customer_postcode }} {{ $bon->order->customer_city }}</span></div>
            <div class="row"><span class="k">Ordernr</span><span class="v">{{ $bon->order->order_number }}</span></div>
        </div>
        <div>
            <h3>Aanlevering</h3>
            <div class="row"><span class="k">Datum</span><span class="v">{{ $bon->picked_up_at?->format('d-m-Y H:i') ?? '—' }}</span></div>
            <div class="row"><span class="k">Gewicht</span><span class="v">{{ $bon->weight_kg ?? '—' }} kg</span></div>
            <div class="row"><span class="k">Dozen</span><span class="v">{{ $bon->order->box_count }}</span></div>
            <div class="row"><span class="k">Rolcontainers</span><span class="v">{{ $bon->order->container_count }}</span></div>
        </div>
    </section>

    <section class="content">
        <h3>Chauffeur</h3>
        <div>{{ $bon->driver_name_snapshot ?? '—' }} &middot; rijbewijs <span style="font-family:monospace;">****{{ $bon->driver_license_last4 ?? '—' }}</span></div>

        @if ($bon->seals->count())
            <div style="margin-top:6mm;">
                <h3>Zegelnummers</h3>
                <div class="seals">
                    @foreach ($bon->seals as $seal)
                        {{ $seal->seal_number }}@if (!$loop->last) &middot; @endif
                    @endforeach
                </div>
            </div>
        @endif

        @if ($bon->notes)
            <div style="margin-top:6mm;">
                <h3>Notities</h3>
                <div style="font-size:10pt;">{{ $bon->notes }}</div>
            </div>
        @endif
    </section>

    <section class="signoff">
        <div>
            @if (!empty($customerSigDataUri))
                <div class="box" style="padding:2mm 0 2mm;height:20mm;text-align:left;">
                    <img src="{{ $customerSigDataUri }}" alt="klant-handtekening" style="max-height:18mm;max-width:60mm;">
                </div>
            @else
                <div class="box">&nbsp;</div>
            @endif
            <div class="lbl">Handtekening klant — {{ $bon->order->customer_name }}</div>
        </div>
        <div>
            @if (!empty($driverSigDataUri))
                <div class="box" style="padding:2mm 0 2mm;height:20mm;text-align:left;">
                    <img src="{{ $driverSigDataUri }}" alt="chauffeur-handtekening" style="max-height:18mm;max-width:60mm;">
                </div>
            @else
                <div class="box">&nbsp;</div>
            @endif
            <div class="lbl">Handtekening chauffeur — {{ $bon->driver_name_snapshot ?? '—' }}</div>
        </div>
    </section>
</div>
</body>
</html>
