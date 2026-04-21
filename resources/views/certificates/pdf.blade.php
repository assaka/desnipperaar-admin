<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>{{ $certificate->certificate_number }} — Certificaat van Vernietiging</title>
<style>
    @page { size: A4; margin: 0; }
    :root { --ink:#0A0A0A; --geel:#F5C518; --rule:#E5E5E5; }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color: var(--ink); background: #FFF; }

    .sheet { width:210mm; min-height:297mm; padding:0; position:relative; }

    .brand-bar { background: var(--geel); padding: 8mm 14mm; font-family:'Arial Black',Arial,sans-serif; font-weight:900; font-size:22pt; letter-spacing:0.04em; }

    .hero { padding:10mm 14mm 6mm; }
    .hero .label { font-family:'Courier New',monospace; font-size:9pt; letter-spacing:0.14em; text-transform:uppercase; color:#555; margin-bottom:4px; }
    .hero h1 { font-weight:900; font-size:22pt; line-height:1.1; margin-bottom:6px; }
    .hero .num { font-family:'Courier New',monospace; font-size:13pt; color:#000; background:var(--geel); display:inline-block; padding:3px 8px; }

    .meta { padding:6mm 14mm; display:grid; grid-template-columns:1fr 1fr; gap:10mm; border-top:1px solid var(--rule); border-bottom:1px solid var(--rule); }
    .meta h3 { font-size:10pt; font-weight:900; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.02em; }
    .meta .row { font-size:11pt; line-height:1.5; }
    .meta .row .k { display:inline-block; width:40%; color:#555; font-size:9pt; }
    .meta .row .v { font-weight:700; }

    .content { padding:8mm 14mm; }
    .content h3 { font-size:11pt; font-weight:900; text-transform:uppercase; letter-spacing:0.02em; margin-bottom:5mm; }

    .seals { font-family:'Courier New',monospace; font-size:9pt; line-height:1.7; background:#FAFAFA; padding:5mm; border-left:3px solid var(--geel); }

    .signoff { padding:8mm 14mm; border-top:1px solid var(--rule); display:grid; grid-template-columns:1fr 1fr; gap:10mm; margin-top:auto; }
    .signoff .box { border-bottom:1px solid #000; padding:12mm 0 2mm; }
    .signoff .box .lbl { font-size:8pt; color:#555; text-transform:uppercase; letter-spacing:0.05em; }

    .trust { background:#F7F7F4; padding:4mm 14mm; font-family:'Courier New',monospace; font-size:8pt; letter-spacing:0.12em; color:#555; text-align:center; text-transform:uppercase; border-top:1px solid var(--rule); }

    .toolbar { padding:6mm 14mm; text-align:right; }
    .toolbar button { background:#0A0A0A; color:#F5C518; font-weight:700; border:0; padding:8px 16px; cursor:pointer; }
    @media print { .toolbar { display:none; } }
</style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">Printen / opslaan als PDF</button></div>

<div class="sheet">

    <header class="brand-bar">DESNIPPERAAR</header>

    <section class="hero">
        <div class="label">Certificaat van Vernietiging</div>
        <h1>Vertrouwelijk vernietigd.</h1>
        <div class="num">{{ $certificate->certificate_number }}</div>
    </section>

    <section class="meta">
        <div>
            <h3>Klant</h3>
            <div class="row"><span class="k">Naam</span><span class="v">{{ $certificate->order->customer_name }}</span></div>
            <div class="row"><span class="k">E-mail</span><span class="v">{{ $certificate->order->customer_email }}</span></div>
            <div class="row"><span class="k">Adres</span><span class="v">{{ $certificate->order->customer_address }}, {{ $certificate->order->customer_postcode }} {{ $certificate->order->customer_city }}</span></div>
            @if ($certificate->order->customer_reference)
                <div class="row"><span class="k">Klantreferentie</span><span class="v">{{ $certificate->order->customer_reference }}</span></div>
            @endif
        </div>
        <div>
            <h3>Order</h3>
            <div class="row"><span class="k">Ordernummer</span><span class="v">{{ $certificate->order->order_number }}</span></div>
            @php $bon = $certificate->order->bons->first(); @endphp
            @if ($bon)
                <div class="row"><span class="k">Bonnummer</span><span class="v">{{ $bon->bon_number }}</span></div>
                <div class="row"><span class="k">Leveringsmethode</span><span class="v">{{ ucfirst($bon->mode) }}service</span></div>
                <div class="row"><span class="k">Datum aanlevering</span><span class="v">{{ $bon->picked_up_at?->format('Y-m-d') }}</span></div>
            @endif
            <div class="row"><span class="k">Datum vernietiging</span><span class="v">{{ $certificate->destroyed_at?->format('Y-m-d') }}</span></div>
        </div>
    </section>

    <section class="content">
        <h3>Inhoud en methode</h3>
        <div class="row" style="line-height:1.8; font-size:11pt;">
            <div>Dozen: <strong>{{ $certificate->order->box_count }}</strong></div>
            <div>Rolcontainers 240 L: <strong>{{ $certificate->order->container_count }}</strong></div>
            <div>Eindgewicht: <strong>{{ $certificate->weight_kg_final ?? '—' }} kg</strong></div>
            <div>Vernietigingsmethode: <strong>{{ $certificate->destruction_method }}</strong></div>
        </div>

        @if ($bon && $bon->seals->count())
            <div style="margin-top:6mm;">
                <h3>Zegelnummers (alle gesloten)</h3>
                <div class="seals">
                    @foreach ($bon->seals as $seal)
                        {{ $seal->seal_number }}@if (!$loop->last) &middot; @endif
                    @endforeach
                </div>
            </div>
        @endif

        @if ($bon)
            <div style="margin-top:6mm;">
                <h3>Chauffeur bij aanlevering</h3>
                <div class="row" style="font-size:11pt;">
                    <div>{{ $bon->driver_name_snapshot ?? '—' }} &middot;
                        rijbewijs <span style="font-family:monospace;">****{{ $bon->driver_license_last4 ?? '—' }}</span></div>
                </div>
            </div>
        @endif
    </section>

    <section class="signoff">
        <div>
            <div class="box">&nbsp;</div>
            <div class="lbl">Handtekening operator</div>
            <div style="font-size:9pt; margin-top:1mm;">{{ $certificate->operator_name ?? '' }}</div>
        </div>
        <div>
            <div class="box">&nbsp;</div>
            <div class="lbl">DeSnipperaar &middot; KvK — &middot; BTW NL—</div>
        </div>
    </section>

    <div class="trust">
        AVG &middot; DIN 66399 &middot; VOG &middot; Verzekerd &middot; &euro;&thinsp;2,5 mln dekking
    </div>

</div>
</body>
</html>
