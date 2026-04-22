<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>{{ $certificate->certificate_number }} — Vernietigingscertificaat</title>
<style>
    @page { size: A4; margin: 0; }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, Helvetica, sans-serif; color:#0A0A0A; background:#FFF; font-size:9pt; line-height:1.35; }
    .sheet { width:210mm; min-height:297mm; padding:0; position:relative; page-break-after:always; }
    .sheet:last-of-type { page-break-after:auto; }

    /* Header band */
    .hdr { background:#F5C518; padding:7mm 14mm 7mm 14mm; position:relative; min-height:30mm; }
    .hdr .logo { display:inline-block; width:18mm; height:18mm; background:#0A0A0A; color:#F5C518; text-align:center;
                 font-family:'Arial Black',Arial,sans-serif; font-weight:900; font-size:22pt; line-height:18mm; border-radius:2mm; margin-right:5mm; vertical-align:middle; }
    .hdr .title { display:inline-block; font-family:'Arial Black',Arial,sans-serif; font-weight:900; font-size:19pt; letter-spacing:-0.02em; line-height:1.05; vertical-align:middle; }
    .hdr .stamp { position:absolute; top:6mm; right:14mm; width:72mm; }
    .hdr .stamp .box { border:1px solid #0A0A0A; background:#F5C518; padding:2mm 3mm; margin-bottom:2mm; }
    .hdr .stamp .lbl { font-size:7pt; font-weight:700; letter-spacing:0.08em; color:#0A0A0A; text-transform:uppercase; }
    .hdr .stamp .val { font-family:'Courier New',monospace; font-size:11pt; font-weight:700; border-bottom:1px solid #0A0A0A; padding-top:1mm; min-height:6mm; }

    .body { padding:6mm 14mm; }
    .grid-dt { display:grid; grid-template-columns:1fr 1fr; gap:10mm; padding:4mm 0 6mm; border-bottom:1px solid #E5E5E5; }
    .grid-dt .lbl { font-size:8pt; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#0A0A0A; }
    .grid-dt .val { font-family:'Courier New',monospace; font-size:11pt; border-bottom:1px solid #0A0A0A; padding-top:1mm; min-height:6mm; }

    h2.sec { font-size:11pt; font-weight:900; letter-spacing:0.02em; margin:5mm 0 2mm; padding-top:3mm; }
    h2.sec .sub { font-weight:400; font-size:9pt; color:#555; }

    .kv { border-bottom:1px solid #DDD; padding:1.5mm 0; }
    .kv .k { font-size:7.5pt; font-weight:700; color:#0A0A0A; }
    .kv .v { font-family:'Courier New',monospace; font-size:10pt; padding-top:0.5mm; min-height:4mm; }

    table.mat { width:100%; border-collapse:collapse; margin-top:2mm; font-size:9pt; }
    table.mat th { background:#F7F7F4; font-weight:700; font-size:7.5pt; letter-spacing:0.05em; text-transform:uppercase; padding:2mm 3mm; text-align:left; border-bottom:1px solid #0A0A0A; }
    table.mat td { padding:2.5mm 3mm; border-bottom:1px solid #E5E5E5; vertical-align:middle; }
    table.mat td.chk { width:5mm; }
    table.mat td.val { font-family:'Courier New',monospace; min-width:40mm; border-bottom:1px solid #E5E5E5; }
    .cbox { display:inline-block; width:3mm; height:3mm; border:1px solid #0A0A0A; margin-right:1mm; vertical-align:-0.5mm; }
    .cbox.on { background:#0A0A0A; }
    .meth { display:inline-block; margin-right:5mm; font-family:'Courier New',monospace; font-size:9pt; }

    .loc { margin:4mm 0; display:grid; grid-template-columns:1fr 1fr; gap:8mm; font-size:9.5pt; }

    .confirm { margin:4mm 0 3mm; padding:3mm 0; font-size:9.5pt; }

    .signs { display:grid; grid-template-columns:1fr 1fr; gap:10mm; margin-top:3mm; }
    .signs .box { border:1px solid #0A0A0A; padding:3mm 4mm; min-height:32mm; }
    .signs .box h4 { font-size:7.5pt; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:2mm; }
    .signs .box .line { border-bottom:1px solid #0A0A0A; padding-top:1mm; min-height:6mm; font-size:10pt; }
    .signs .box .sub { font-size:7.5pt; font-weight:700; margin-top:2mm; }
    .signs .sig-img { max-height:14mm; max-width:100%; display:block; }

    footer.pg { position:absolute; bottom:0; left:0; right:0; }
    footer.pg .co { padding:3mm 14mm; font-size:8pt; color:#555; }
    footer.pg .badges { background:#F5C518; padding:2.5mm 14mm; text-align:center; font-weight:700; font-size:8pt; letter-spacing:0.1em; }

    /* Page 2 — Bijlage A */
    .bijlage-info { padding:4mm 14mm; font-size:9pt; color:#333; }
    table.bijlage { width:calc(100% - 28mm); margin:0 14mm; border-collapse:collapse; font-size:9pt; }
    table.bijlage th { background:#F7F7F4; font-weight:700; font-size:7.5pt; text-transform:uppercase; letter-spacing:0.05em; padding:2mm 3mm; border-bottom:1px solid #0A0A0A; text-align:left; }
    table.bijlage td { padding:1.8mm 3mm; border-bottom:1px solid #E5E5E5; font-family:'Courier New',monospace; font-size:9pt; vertical-align:middle; }
    table.bijlage td.n  { width:10mm; text-align:center; color:#555; font-weight:700; }
    table.bijlage td.v  { text-align:center; width:22mm; }
    .bijlage-footer { padding:4mm 14mm; font-size:8.5pt; }

    .toolbar { position:fixed; top:8px; right:8px; z-index:100; }
    .toolbar button { background:#0A0A0A; color:#F5C518; font-weight:700; border:0; padding:8px 16px; cursor:pointer; }
    @media print { .toolbar { display:none !important; } }
</style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">Printen / opslaan als PDF</button></div>
@php
    $order       = $certificate->order;
    $customer    = $order->customer;
    $bon         = $order->bons->first();
    $mediaSource = !empty($bon?->actual_media) ? $bon->actual_media : ($order->media_items ?? []);
    $mediaInt    = fn($k) => (int) ($mediaSource[$k] ?? 0);
    $method      = strtoupper((string) $certificate->destruction_method);

    $hasPaperMethod = str_contains($method, 'P-4') || str_contains($method, 'P-5') || str_contains($method, 'P-6');
    $paperMethods = ['P-4' => str_contains($method, 'P-4'),
                     'P-5' => str_contains($method, 'P-5') || !$hasPaperMethod,   // P-5 is the standard default
                     'P-6' => str_contains($method, 'P-6')];
    $hddMethods   = ['H-3' => str_contains($method, 'H-3'),
                     'H-4' => str_contains($method, 'H-4'),
                     'H-5' => str_contains($method, 'H-5')];
    $eMethods3    = str_contains($method, 'E-3');
    $eMethods4    = str_contains($method, 'E-4');
    $tMethods2    = str_contains($method, 'T-2');
    $tMethods3    = str_contains($method, 'T-3');

    $locOnsite  = $bon && in_array($bon->mode, ['mobiel']);
    $locDepot   = !$locOnsite;

    $custSigPath   = $bon?->customer_signature_path;
    $opSigPath     = $certificate->operator_signature_path ?: $bon?->driver_signature_path;

    $toDataUri = function ($path) {
        if (!$path) return null;
        try {
            if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) return null;
            return 'data:image/png;base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('local')->get($path));
        } catch (\Throwable $e) { return null; }
    };
    $custSig = $toDataUri($custSigPath);
    $opSig   = $toDataUri($opSigPath);

    $co = config('desnipperaar.company');
@endphp

<div class="sheet">
    <header class="hdr">
        <span class="logo">DS</span><span class="title">VERNIETIGINGS-<br>CERTIFICAAT</span>
        <div class="stamp">
            <div class="box">
                <div class="lbl">Certificaatnummer</div>
                <div class="val">{{ $certificate->certificate_number }}</div>
            </div>
            <div class="box">
                <div class="lbl">Verwerkersovereenkomst-nr / -datum</div>
                <div class="val">—</div>
            </div>
        </div>
    </header>

    <div class="body">
        <div class="grid-dt">
            <div>
                <div class="lbl">Datum</div>
                <div class="val">{{ $certificate->destroyed_at?->format('d-m-Y') ?? '—' }}</div>
            </div>
            <div>
                <div class="lbl">Tijdstip</div>
                <div class="val">{{ $certificate->destroyed_at?->format('H:i') ?? '—' }}</div>
            </div>
        </div>

        <h2 class="sec">Opdrachtgever</h2>
        <div class="kv"><div class="k">Bedrijf / organisatie</div>
            <div class="v">{{ $customer?->company ?: '—' }}</div></div>
        <div class="kv"><div class="k">Adres + postcode + plaats</div>
            <div class="v">{{ trim($order->customer_address . ', ' . $order->customer_postcode . ' ' . $order->customer_city, ', ') }}</div></div>
        <div class="kv"><div class="k">Contactpersoon</div>
            <div class="v">{{ $order->customer_name }}</div></div>
        <div class="kv"><div class="k">E-mail / telefoon</div>
            <div class="v">{{ $order->customer_email }}@if ($order->customer_phone) &middot; {{ $order->customer_phone }}@endif</div></div>
        <div class="kv"><div class="k">KvK-nummer (opdrachtgever)</div>
            <div class="v">{{ $customer?->kvk ?? '—' }}</div></div>

        <h2 class="sec">Ophaal / Chain of Custody <span class="sub">(indien off-site verzameld)</span></h2>
        <div class="kv"><div class="k">Ophaaladres (indien anders dan opdrachtgever)</div>
            <div class="v">—</div></div>
        <div class="kv"><div class="k">Verzegelde container- / sealnummer</div>
            <div class="v">@if ($bon && $bon->seals->count()){{ $bon->seals->pluck('seal_number')->implode(' · ') }}@else —@endif</div></div>

        <h2 class="sec">Vernietigde materialen</h2>
        <table class="mat">
            <thead><tr><th></th><th>Materiaal</th><th>Aantal / gewicht</th><th>Methode (DIN 66399 / ISO 21964)</th></tr></thead>
            <tbody>
                @if ($order->box_count > 0)
                    <tr>
                        <td class="chk"><span class="cbox on"></span></td>
                        <td>Papier / dossiers (dozen)</td>
                        <td class="val">{{ $order->box_count }} {{ $order->box_count == 1 ? 'doos' : 'dozen' }}</td>
                        <td>
                            @foreach ($paperMethods as $m => $on)
                                <span class="meth"><span class="cbox {{ $on ? 'on' : '' }}"></span>{{ $m }}</span>
                            @endforeach
                        </td>
                    </tr>
                @endif
                @if ($order->container_count > 0)
                    <tr>
                        <td class="chk"><span class="cbox on"></span></td>
                        <td>Papier / dossiers (rolcontainers 240 L)</td>
                        <td class="val">{{ $order->container_count }} {{ $order->container_count == 1 ? 'rolcontainer' : 'rolcontainers' }}@if ($certificate->weight_kg_final) &middot; {{ number_format($certificate->weight_kg_final, 1, ',', '.') }} kg @endif</td>
                        <td>
                            @foreach ($paperMethods as $m => $on)
                                <span class="meth"><span class="cbox {{ $on ? 'on' : '' }}"></span>{{ $m }}</span>
                            @endforeach
                        </td>
                    </tr>
                @endif
                @if ($mediaInt('hdd') > 0)
                    <tr>
                        <td class="chk"><span class="cbox on"></span></td>
                        <td>Harde schijven (HDD)</td>
                        <td class="val">{{ $mediaInt('hdd') }} stuks</td>
                        <td>
                            @foreach ($hddMethods as $m => $on)
                                <span class="meth"><span class="cbox {{ $on ? 'on' : '' }}"></span>{{ $m }}</span>
                            @endforeach
                        </td>
                    </tr>
                @endif
                @if ($mediaInt('ssd') > 0)
                    <tr>
                        <td class="chk"><span class="cbox on"></span></td>
                        <td>SSDs</td>
                        <td class="val">{{ $mediaInt('ssd') }} stuks</td>
                        <td>
                            <span class="meth"><span class="cbox {{ $eMethods3 ? 'on' : '' }}"></span>E-3</span>
                            <span class="meth"><span class="cbox {{ $eMethods4 ? 'on' : '' }}"></span>E-4</span>
                        </td>
                    </tr>
                @endif
                @if ($mediaInt('phone') > 0)
                    <tr>
                        <td class="chk"><span class="cbox on"></span></td>
                        <td>Mobiele telefoons</td>
                        <td class="val">{{ $mediaInt('phone') }} stuks</td>
                        <td>
                            <span class="meth"><span class="cbox {{ $eMethods3 ? 'on' : '' }}"></span>E-3</span>
                            <span class="meth"><span class="cbox {{ $eMethods4 ? 'on' : '' }}"></span>E-4</span>
                        </td>
                    </tr>
                @endif
                @if ($mediaInt('usb') > 0)
                    <tr>
                        <td class="chk"><span class="cbox on"></span></td>
                        <td>USB / geheugenkaarten</td>
                        <td class="val">{{ $mediaInt('usb') }} stuks</td>
                        <td>
                            <span class="meth"><span class="cbox {{ $eMethods3 ? 'on' : '' }}"></span>E-3</span>
                            <span class="meth"><span class="cbox {{ $eMethods4 ? 'on' : '' }}"></span>E-4</span>
                        </td>
                    </tr>
                @endif
                @if ($mediaInt('laptop') > 0)
                    <tr>
                        <td class="chk"><span class="cbox on"></span></td>
                        <td>Anders (laptop / overig)</td>
                        <td class="val">{{ $mediaInt('laptop') }} stuks/kg</td>
                        <td>Klasse: ___</td>
                    </tr>
                @endif
            </tbody>
        </table>
        <p style="font-size:8pt; color:#555; margin-top:2mm;">Voor serienummers, IMEIs en asset-tags per stuk: zie Bijlage A op de volgende pagina.</p>

        <div class="loc">
            <div><strong style="font-size:7.5pt; letter-spacing:0.08em; text-transform:uppercase;">Locatie van vernietiging</strong><br>
                <span class="meth"><span class="cbox {{ $locOnsite ? 'on' : '' }}"></span>Mobiel op locatie opdrachtgever</span><br>
                <span class="meth"><span class="cbox {{ $locDepot ? 'on' : '' }}"></span>Bij depot Amsterdam-Noord (1034DN)</span>
            </div>
            <div><strong style="font-size:7.5pt; letter-spacing:0.08em; text-transform:uppercase;">Restmateriaal afgevoerd via</strong><br>
                <span style="font-family:'Courier New',monospace; display:inline-block; min-width:55mm; border-bottom:1px solid #0A0A0A;">&nbsp;</span><br>
                <span style="font-size:8.5pt; color:#555;">(gecertificeerde recycler)</span>
            </div>
        </div>

        <p class="confirm">
            DeSnipperaar bevestigt hierbij dat de bovenstaande materialen volledig en onomkeerbaar zijn vernietigd,
            conform AVG, DIN 66399, NEN-15713 en ISO 21964.
        </p>

        <div class="signs">
            <div class="box">
                <h4>Operator (DeSnipperaar)</h4>
                <div class="sub">Naam:</div>
                <div class="line">{{ $certificate->operator_name ?? '' }}</div>
                <div class="sub">Datum:</div>
                <div class="line">{{ $certificate->destroyed_at?->format('d-m-Y') ?? '' }}</div>
                <div class="sub">Handtekening:</div>
                <div class="line">@if ($opSig)<img src="{{ $opSig }}" class="sig-img" alt="handtekening operator">@endif</div>
            </div>
            <div class="box">
                <h4>Opdrachtgever / getuige</h4>
                <div class="sub">Naam:</div>
                <div class="line">{{ $order->customer_name }}</div>
                <div class="sub">Datum:</div>
                <div class="line">{{ $bon?->picked_up_at?->format('d-m-Y') ?? '' }}</div>
                <div class="sub">Handtekening:</div>
                <div class="line">@if ($custSig)<img src="{{ $custSig }}" class="sig-img" alt="handtekening klant">@endif</div>
            </div>
        </div>
    </div>

    <footer class="pg">
        <div class="co">
            {{ $co['name'] }} &middot; Mobiele documentvernietiging op locatie binnen 20 km van Amsterdam-Noord (1034DN)<br>
            {{ $co['email'] }} &middot; {{ $co['website'] }} &middot; KvK {{ $co['kvk'] ?: '[nummer]' }} &middot; BTW {{ $co['btw'] ?: '[nummer]' }}
        </div>
        <div class="badges">AVG-CONFORM &nbsp;·&nbsp; DIN 66399 &nbsp;·&nbsp; NEN-15713 &nbsp;·&nbsp; ISO 21964 &nbsp;·&nbsp; VERZEKERD &nbsp;·&nbsp; VOG</div>
    </footer>
</div>

{{-- Page 2 — Bijlage A: optioneel (wordt alleen gebruikt als er serial numbers per stuk zijn geregistreerd, voor audit). --}}
<div class="sheet">
    <header class="hdr">
        <span class="logo">DS</span><span class="title">BIJLAGE A<br><span style="font-size:10pt; font-weight:700;">SERIENUMMERS / IMEI / ASSET-TAGS</span></span>
        <div class="stamp">
            <div class="box">
                <div class="lbl">Behoort bij certificaatnummer</div>
                <div class="val">{{ $certificate->certificate_number }}</div>
            </div>
        </div>
    </header>

    <div class="bijlage-info">Lijst van afzonderlijk vernietigde gegevensdragers met serienummer of IMEI per stuk.</div>

    <table class="bijlage">
        <thead><tr><th class="n">Regel</th><th>Type (HDD/SSD/Tel/Tape/USB) &nbsp; Serienummer / IMEI / Asset-tag</th><th class="v">Geverifieerd</th></tr></thead>
        <tbody>
            @for ($i = 1; $i <= 20; $i++)
                <tr>
                    <td class="n">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</td>
                    <td>&nbsp;</td>
                    <td class="v"><span class="cbox"></span></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="bijlage-footer">
        <strong>Totaal aantal assets:</strong> <span style="font-family:'Courier New',monospace; display:inline-block; min-width:25mm; border-bottom:1px solid #0A0A0A;">&nbsp;</span> stuks
        &nbsp;&nbsp;&nbsp;
        <strong>Operator parafeen per pagina:</strong> <span style="font-family:'Courier New',monospace; display:inline-block; min-width:35mm; border-bottom:1px solid #0A0A0A;">&nbsp;</span>
    </div>

    <footer class="pg">
        <div class="co">
            {{ $co['name'] }} &middot; Mobiele documentvernietiging op locatie binnen 20 km van Amsterdam-Noord (1034DN)<br>
            {{ $co['email'] }} &middot; {{ $co['website'] }} &middot; KvK {{ $co['kvk'] ?: '[nummer]' }} &middot; BTW {{ $co['btw'] ?: '[nummer]' }}
        </div>
        <div class="badges">AVG-CONFORM &nbsp;·&nbsp; DIN 66399 &nbsp;·&nbsp; NEN-15713 &nbsp;·&nbsp; ISO 21964 &nbsp;·&nbsp; VERZEKERD &nbsp;·&nbsp; VOG</div>
    </footer>
</div>

</body>
</html>
