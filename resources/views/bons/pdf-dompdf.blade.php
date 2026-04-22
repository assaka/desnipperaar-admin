<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>{{ $bon->bon_number }} — Ophaalbon</title>
<style>
    @page { size: A4; margin: 0; }
    body { font-family: Arial, Helvetica, sans-serif; color: #0A0A0A; font-size: 10pt; line-height: 1.4; margin: 0; padding: 0; }
    .brand { background: #F5C518; padding: 8mm 14mm; font-weight: 900; font-size: 20pt; letter-spacing: 0.04em; }
    .wrap { padding: 8mm 14mm; }
    h1 { font-weight: 900; font-size: 20pt; margin: 0 0 4mm; }
    .num { font-family: 'Courier New', monospace; font-size: 12pt; background: #F5C518; padding: 2mm 4mm; display: inline-block; }
    .eyebrow { font-family: 'Courier New', monospace; font-size: 9pt; letter-spacing: 0.1em; text-transform: uppercase; color: #555; margin-bottom: 2mm; }
    table { width: 100%; border-collapse: collapse; }
    .meta { margin-top: 6mm; border-top: 1px solid #E5E5E5; border-bottom: 1px solid #E5E5E5; padding: 4mm 0; }
    .meta-col { width: 50%; vertical-align: top; padding-right: 6mm; }
    .meta-col h3 { font-size: 10pt; font-weight: 900; text-transform: uppercase; margin: 0 0 2mm; }
    .row { margin-bottom: 1mm; font-size: 10pt; }
    .row .k { display: inline-block; width: 38%; color: #555; font-size: 9pt; }
    .row .v { font-weight: 700; }
    .seals { background: #FAFAFA; padding: 4mm; border-left: 3px solid #F5C518; margin-top: 3mm; font-family: 'Courier New', monospace; font-size: 9pt; line-height: 1.7; }
    .signs { margin-top: 10mm; }
    .signs td { width: 50%; padding-right: 8mm; vertical-align: top; }
    .sig-box { border: 1px solid #DDD; height: 30mm; padding: 2mm; text-align: center; }
    .sig-box img { max-height: 26mm; max-width: 100%; }
    .sig-lbl { font-size: 8pt; color: #555; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 2mm; }
    .trust { background: #F7F7F4; padding: 4mm 14mm; font-family: 'Courier New', monospace; font-size: 8pt; letter-spacing: 0.1em; color: #555; text-align: center; text-transform: uppercase; border-top: 1px solid #E5E5E5; margin-top: 10mm; }
</style>
</head>
<body>

<div class="brand">DESNIPPERAAR</div>

<div class="wrap">
    <div class="eyebrow">{{ ucfirst($bon->mode) }}bon</div>
    <h1>Afhaalbewijs</h1>
    <span class="num">{{ $bon->bon_number }}</span>

    <table class="meta">
        <tr>
            <td class="meta-col">
                <h3>Klant</h3>
                @if ($bon->order->customer?->company)
                    <div class="row"><span class="k">Bedrijf</span><span class="v">{{ $bon->order->customer->company }}</span></div>
                @endif
                <div class="row"><span class="k">Naam</span><span class="v">{{ $bon->order->customer_name }}</span></div>
                @if ($bon->order->customer_address)
                    <div class="row"><span class="k">Adres</span><span class="v">{{ $bon->order->customer_address }}</span></div>
                @endif
                <div class="row"><span class="k">Postcode</span><span class="v"><span style="font-family:'Courier New',monospace;">{{ $bon->order->customer_postcode }}</span> {{ $bon->order->customer_city }}</span></div>
                <div class="row"><span class="k">Ordernr</span><span class="v">{{ $bon->order->order_number }}</span></div>
            </td>
            <td class="meta-col">
                <h3>Aanlevering</h3>
                <div class="row"><span class="k">Datum</span><span class="v">{{ $bon->picked_up_at?->format('d-m-Y H:i') ?? '—' }}</span></div>
                <div class="row"><span class="k">Gewicht</span><span class="v">{{ $bon->weight_kg ?? '—' }} kg</span></div>
                @php
                    $boxes   = $bon->actual_boxes     ?? $bon->order->box_count;
                    $cntrs   = $bon->actual_containers ?? $bon->order->container_count;
                    $boxDiff = $bon->actual_boxes     !== null && $bon->actual_boxes     !== $bon->order->box_count;
                    $cntDiff = $bon->actual_containers !== null && $bon->actual_containers !== $bon->order->container_count;
                @endphp
                <div class="row"><span class="k">Dozen</span><span class="v">{{ $boxes }}@if ($boxDiff) <span style="color:#555;font-size:8pt;">(besteld: {{ $bon->order->box_count }})</span>@endif</span></div>
                <div class="row"><span class="k">Rolcontainers</span><span class="v">{{ $cntrs }}@if ($cntDiff) <span style="color:#555;font-size:8pt;">(besteld: {{ $bon->order->container_count }})</span>@endif</span></div>
                @php $actualMedia = $bon->actual_media ?? $bon->order->media_items ?? []; @endphp
                @if (!empty($actualMedia))
                    @foreach ($actualMedia as $k => $q)
                        @if ((int) $q > 0)
                            @php $lbl = ['hdd'=>'HDD','ssd'=>'SSD/NVMe','usb'=>'USB/SD','phone'=>'Telefoon','laptop'=>'Laptop'][$k] ?? ucfirst($k); @endphp
                            <div class="row"><span class="k">{{ $lbl }}</span><span class="v">{{ $q }}</span></div>
                        @endif
                    @endforeach
                @endif
                <div class="row"><span class="k">Chauffeur</span><span class="v">{{ $bon->driver_name_snapshot ?? '—' }}</span></div>
                <div class="row"><span class="k">Rijbewijs</span><span class="v" style="font-family:'Courier New',monospace;">****{{ $bon->driver_license_last4 ?? '—' }}</span></div>
            </td>
        </tr>
    </table>

    @if ($bon->seals->count())
        <h3 style="font-size:10pt;font-weight:900;text-transform:uppercase;margin:6mm 0 2mm;">Zegelnummers</h3>
        <div class="seals">
            @foreach ($bon->seals as $seal)
                {{ $seal->seal_number }}@if (!$loop->last) · @endif
            @endforeach
        </div>
    @endif

    @if ($bon->notes)
        <h3 style="font-size:10pt;font-weight:900;text-transform:uppercase;margin:6mm 0 2mm;">Notities</h3>
        <div style="font-size:9pt;">{{ $bon->notes }}</div>
    @endif

    <table class="signs">
        <tr>
            <td>
                <div class="sig-box">
                    @if ($bon->customer_signature_path && file_exists(storage_path('app/'.$bon->customer_signature_path)))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(storage_path('app/'.$bon->customer_signature_path))) }}" alt="Handtekening klant">
                    @else
                        &nbsp;
                    @endif
                </div>
                <div class="sig-lbl">Handtekening klant — {{ $bon->order->customer_name }}</div>
            </td>
            <td>
                <div class="sig-box">
                    @if ($bon->driver_signature_path && file_exists(storage_path('app/'.$bon->driver_signature_path)))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(storage_path('app/'.$bon->driver_signature_path))) }}" alt="Handtekening chauffeur">
                    @else
                        &nbsp;
                    @endif
                </div>
                <div class="sig-lbl">Handtekening chauffeur — {{ $bon->driver_name_snapshot ?? '—' }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="trust">AVG · DIN 66399 · VOG · Verzekerd · € 2,5 mln dekking</div>

</body>
</html>
