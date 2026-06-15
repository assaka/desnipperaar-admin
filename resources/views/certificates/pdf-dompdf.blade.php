<!DOCTYPE html>
@php $loc = in_array($locale ?? 'nl', ['nl', 'en', 'fr', 'es'], true) ? ($locale ?? 'nl') : 'nl'; @endphp
<html lang="{{ $loc }}">
<head>
<meta charset="UTF-8">
<title>{{ $certificate->certificate_number }} — {{ ['nl' => 'Vernietigingscertificaat', 'en' => 'Certificate of destruction', 'fr' => 'Certificat de destruction', 'es' => 'Certificado de destrucción'][$loc] }}</title>
<style>
    @page { size: A4; margin: 0; }
    body { font-family: Arial, Helvetica, sans-serif; color: #0A0A0A; font-size: 10pt; line-height: 1.4; margin: 0; padding: 0; }
    .brand { background: #F5C518; padding: 8mm 14mm; }
    .brand .logo { font-weight: 900; font-size: 20pt; letter-spacing: 0.04em; }
    .brand .title { font-weight: 900; font-size: 15pt; margin-top: 1mm; }
    .wrap { padding: 8mm 14mm; }
    .num { font-family: 'Courier New', monospace; font-size: 12pt; background: #F5C518; padding: 2mm 4mm; display: inline-block; margin-bottom: 4mm; }
    table { width: 100%; border-collapse: collapse; }
    .dt { margin: 2mm 0 5mm; }
    .dt td { width: 50%; font-size: 10pt; padding-right: 6mm; }
    .dt .k { color: #555; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.05em; }
    .dt .v { font-weight: 700; font-family: 'Courier New', monospace; }
    h2.sec { font-size: 11pt; font-weight: 900; text-transform: uppercase; letter-spacing: 0.03em; margin: 5mm 0 2mm; border-bottom: 1px solid #0A0A0A; padding-bottom: 1mm; }
    .row { margin-bottom: 1mm; font-size: 10pt; }
    .row .k { display: inline-block; width: 34%; color: #555; font-size: 9pt; }
    .row .v { font-weight: 700; }
    table.mat { margin-top: 2mm; }
    table.mat th { background: #0A0A0A; color: #F5C518; padding: 2mm 3mm; text-align: left; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.03em; }
    table.mat td { padding: 2mm 3mm; border-bottom: 1px solid #DDD; font-size: 9.5pt; vertical-align: top; }
    table.mat td.method { font-family: 'Courier New', monospace; font-size: 9pt; }
    .seals { background: #FAFAFA; padding: 4mm; border-left: 3px solid #F5C518; margin-top: 2mm; font-family: 'Courier New', monospace; font-size: 9pt; line-height: 1.7; }
    .confirm { margin: 5mm 0 3mm; padding: 4mm; background: #F7F7F4; border-left: 3px solid #F5C518; font-size: 9.5pt; }
    .signs { margin-top: 4mm; }
    .signs td { width: 50%; padding-right: 8mm; vertical-align: top; }
    .sig-box { border: 1px solid #DDD; padding: 2mm 3mm; min-height: 30mm; }
    .sig-box h4 { font-size: 8pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1mm; }
    .sig-box .sub { font-size: 8pt; color: #555; margin-top: 2mm; }
    .sig-box .line { border-bottom: 1px solid #0A0A0A; min-height: 6mm; padding-top: 1mm; }
    .sig-box img { max-height: 14mm; max-width: 100%; }
    .trust { background: #F7F7F4; padding: 4mm 14mm; font-family: 'Courier New', monospace; font-size: 8pt; letter-spacing: 0.08em; color: #555; text-align: center; text-transform: uppercase; border-top: 1px solid #E5E5E5; margin-top: 8mm; }
</style>
</head>
<body>
@php
    $order    = $certificate->order;
    $customer = $order->customer;
    $bon      = $order->bons->first();
    $src      = !empty($bon?->actual_media) ? $bon->actual_media : ($order->media_items ?? []);
    $mi       = fn ($k) => (int) ($src[$k] ?? 0);
    $method   = $certificate->destruction_method ?: 'DIN 66399';
    $co       = config('desnipperaar.company');
    $locOnsite = $bon && in_array($bon->mode, ['mobiel']);
    $weightSuffix = $certificate->weight_kg_final ? ' · ' . number_format($certificate->weight_kg_final, 1, ',', '.') . ' kg' : '';

    $toDataUri = function ($path) {
        if (!$path) return null;
        try {
            if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) return null;
            return 'data:image/png;base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('local')->get($path));
        } catch (\Throwable $e) { return null; }
    };
    $opSig   = $toDataUri($certificate->operator_signature_path ?: $bon?->driver_signature_path);
    $custSig = $toDataUri($bon?->customer_signature_path);

    $L = [
        'nl' => [
            'sep' => ':',
            'title' => 'VERNIETIGINGSCERTIFICAAT', 'certNo' => 'Certificaatnummer',
            'date' => 'Datum', 'time' => 'Tijdstip', 'client' => 'Opdrachtgever',
            'company' => 'Bedrijf / organisatie', 'address' => 'Adres', 'contact' => 'Contactpersoon',
            'emailphone' => 'E-mail / telefoon', 'coc' => 'Ophaal / chain of custody', 'seals' => 'Verzegelde container- / sealnummers',
            'materials' => 'Vernietigde materialen', 'colMat' => 'Materiaal', 'colQty' => 'Aantal / gewicht', 'colMethod' => 'Methode',
            'paperBox' => 'Papier / dossiers (dozen)', 'paperCnt' => 'Papier / dossiers (rolcontainers 240 L)',
            'hdd' => 'Harde schijven (HDD)', 'ssd' => 'SSDs', 'phone' => 'Mobiele telefoons', 'usb' => 'USB / geheugenkaarten', 'laptop' => 'Anders (laptop / overig)',
            'box' => 'doos', 'boxes' => 'dozen', 'cnt' => 'rolcontainer', 'cnts' => 'rolcontainers', 'items' => 'stuks',
            'loc' => 'Locatie van vernietiging', 'onsite' => 'Mobiel op locatie opdrachtgever', 'depot' => 'Bij depot Amsterdam-Noord (1034DN)',
            'confirm' => 'DeSnipperaar bevestigt hierbij dat de bovenstaande materialen volledig en onomkeerbaar zijn vernietigd, conform AVG, DIN 66399, NEN-15713 en ISO 21964.',
            'operator' => 'Operator (DeSnipperaar)', 'witness' => 'Opdrachtgever / getuige', 'name' => 'Naam', 'signature' => 'Handtekening',
            'tagline' => 'Mobiele documentvernietiging op locatie binnen 20 km van Amsterdam-Noord (1034DN)',
            'badges' => 'AVG-CONFORM · DIN 66399 · NEN-15713 · ISO 21964 · VERZEKERD · VOG',
        ],
        'en' => [
            'sep' => ':',
            'title' => 'CERTIFICATE OF DESTRUCTION', 'certNo' => 'Certificate number',
            'date' => 'Date', 'time' => 'Time', 'client' => 'Client',
            'company' => 'Company / organisation', 'address' => 'Address', 'contact' => 'Contact person',
            'emailphone' => 'Email / phone', 'coc' => 'Chain of custody', 'seals' => 'Sealed container / seal numbers',
            'materials' => 'Destroyed materials', 'colMat' => 'Material', 'colQty' => 'Quantity / weight', 'colMethod' => 'Method',
            'paperBox' => 'Paper / files (boxes)', 'paperCnt' => 'Paper / files (240 L roll containers)',
            'hdd' => 'Hard drives (HDD)', 'ssd' => 'SSDs', 'phone' => 'Mobile phones', 'usb' => 'USB / memory cards', 'laptop' => 'Other (laptop / misc.)',
            'box' => 'box', 'boxes' => 'boxes', 'cnt' => 'roll container', 'cnts' => 'roll containers', 'items' => 'items',
            'loc' => 'Location of destruction', 'onsite' => 'Mobile, at client location', 'depot' => 'At depot Amsterdam-Noord (1034DN)',
            'confirm' => 'DeSnipperaar hereby confirms that the materials listed above have been fully and irreversibly destroyed, in accordance with GDPR, DIN 66399, NEN-15713 and ISO 21964.',
            'operator' => 'Operator (DeSnipperaar)', 'witness' => 'Client / witness', 'name' => 'Name', 'signature' => 'Signature',
            'tagline' => 'Mobile document destruction on site within 20 km of Amsterdam-Noord (1034DN)',
            'badges' => 'GDPR · DIN 66399 · NEN-15713 · ISO 21964 · INSURED · VOG-SCREENED',
        ],
        'fr' => [
            'sep' => "\u{00A0}:",
            'title' => 'CERTIFICAT DE DESTRUCTION', 'certNo' => 'Numéro de certificat',
            'date' => 'Date', 'time' => 'Heure', 'client' => 'Donneur d\'ordre',
            'company' => 'Société / organisation', 'address' => 'Adresse', 'contact' => 'Personne de contact',
            'emailphone' => 'E-mail / téléphone', 'coc' => 'Enlèvement / chaîne de possession', 'seals' => 'Numéros de conteneur scellé / scellés',
            'materials' => 'Matériaux détruits', 'colMat' => 'Matériau', 'colQty' => 'Quantité / poids', 'colMethod' => 'Méthode',
            'paperBox' => 'Papier / dossiers (cartons)', 'paperCnt' => 'Papier / dossiers (conteneurs roulants 240 L)',
            'hdd' => 'Disques durs (HDD)', 'ssd' => 'SSD', 'phone' => 'Téléphones mobiles', 'usb' => 'USB / cartes mémoire', 'laptop' => 'Autres (ordinateur portable / divers)',
            'box' => 'carton', 'boxes' => 'cartons', 'cnt' => 'conteneur roulant', 'cnts' => 'conteneurs roulants', 'items' => 'unités',
            'loc' => 'Lieu de destruction', 'onsite' => 'Mobile sur le site du donneur d\'ordre', 'depot' => 'Au dépôt Amsterdam-Noord (1034DN)',
            'confirm' => 'DeSnipperaar confirme par la présente que les matériaux mentionnés ci-dessus ont été détruits de manière complète et irréversible, conformément au RGPD, à la norme DIN 66399, NEN-15713 et ISO 21964.',
            'operator' => 'Opérateur (DeSnipperaar)', 'witness' => 'Donneur d\'ordre / témoin', 'name' => 'Nom', 'signature' => 'Signature',
            'tagline' => 'Destruction mobile de documents sur site dans un rayon de 20 km autour d\'Amsterdam-Noord (1034DN)',
            'badges' => 'CONFORME RGPD · DIN 66399 · NEN-15713 · ISO 21964 · ASSURÉ · PERSONNEL AVEC VOG',
        ],
        'es' => [
            'sep' => ':',
            'title' => 'CERTIFICADO DE DESTRUCCIÓN', 'certNo' => 'Número de certificado',
            'date' => 'Fecha', 'time' => 'Hora', 'client' => 'Cliente',
            'company' => 'Empresa / organización', 'address' => 'Dirección', 'contact' => 'Persona de contacto',
            'emailphone' => 'Correo / teléfono', 'coc' => 'Recogida / cadena de custodia', 'seals' => 'Números de contenedor sellado / precintos',
            'materials' => 'Materiales destruidos', 'colMat' => 'Material', 'colQty' => 'Cantidad / peso', 'colMethod' => 'Método',
            'paperBox' => 'Papel / expedientes (cajas)', 'paperCnt' => 'Papel / expedientes (contenedores con ruedas 240 L)',
            'hdd' => 'Discos duros (HDD)', 'ssd' => 'SSD', 'phone' => 'Teléfonos móviles', 'usb' => 'USB / tarjetas de memoria', 'laptop' => 'Otros (portátil / varios)',
            'box' => 'caja', 'boxes' => 'cajas', 'cnt' => 'contenedor con ruedas', 'cnts' => 'contenedores con ruedas', 'items' => 'unidades',
            'loc' => 'Lugar de destrucción', 'onsite' => 'Móvil en las instalaciones del cliente', 'depot' => 'En el depósito Amsterdam-Noord (1034DN)',
            'confirm' => 'DeSnipperaar confirma por la presente que los materiales indicados arriba han sido destruidos de forma completa e irreversible, conforme al RGPD, DIN 66399, NEN-15713 e ISO 21964.',
            'operator' => 'Operario (DeSnipperaar)', 'witness' => 'Cliente / testigo', 'name' => 'Nombre', 'signature' => 'Firma',
            'tagline' => 'Destrucción móvil de documentos in situ en un radio de 20 km de Amsterdam-Noord (1034DN)',
            'badges' => 'CONFORME RGPD · DIN 66399 · NEN-15713 · ISO 21964 · ASEGURADO · PERSONAL CON VOG',
        ],
    ][$loc];
@endphp

<div class="brand">
    <div class="logo">DESNIPPERAAR</div>
    <div class="title">{{ $L['title'] }}</div>
</div>

<div class="wrap">
    <span class="num">{{ $certificate->certificate_number }}</span>

    <table class="dt">
        <tr>
            <td><div class="k">{{ $L['date'] }}</div><div class="v">{{ $certificate->destroyed_at?->format('d-m-Y') ?? '—' }}</div></td>
            <td><div class="k">{{ $L['time'] }}</div><div class="v">{{ $certificate->destroyed_at?->format('H:i') ?? '—' }}</div></td>
        </tr>
    </table>

    <h2 class="sec">{{ $L['client'] }}</h2>
    <div class="row"><span class="k">{{ $L['company'] }}</span><span class="v">{{ $customer?->company ?: '—' }}</span></div>
    <div class="row"><span class="k">{{ $L['address'] }}</span><span class="v">{{ trim($order->customer_address . ', ' . $order->customer_postcode . ' ' . $order->customer_city, ', ') }}</span></div>
    <div class="row"><span class="k">{{ $L['contact'] }}</span><span class="v">{{ $order->customer_name }}</span></div>
    <div class="row"><span class="k">{{ $L['emailphone'] }}</span><span class="v">{{ $order->customer_email }}@if ($order->customer_phone) &middot; {{ $order->customer_phone }}@endif</span></div>

    @if ($bon && $bon->seals->count())
        <h2 class="sec">{{ $L['coc'] }}</h2>
        <div class="row"><span class="k">{{ $L['seals'] }}</span></div>
        <div class="seals">{{ $bon->seals->pluck('seal_number')->implode(' · ') }}</div>
    @endif

    <h2 class="sec">{{ $L['materials'] }}</h2>
    <table class="mat">
        <thead><tr><th>{{ $L['colMat'] }}</th><th>{{ $L['colQty'] }}</th><th>{{ $L['colMethod'] }}</th></tr></thead>
        <tbody>
            @if ($order->box_count > 0)
                <tr><td>{{ $L['paperBox'] }}</td><td>{{ $order->box_count }} {{ $order->box_count == 1 ? $L['box'] : $L['boxes'] }}</td><td class="method">{{ $method }}</td></tr>
            @endif
            @if ($order->container_count > 0)
                <tr><td>{{ $L['paperCnt'] }}</td><td>{{ $order->container_count }} {{ $order->container_count == 1 ? $L['cnt'] : $L['cnts'] }}{{ $weightSuffix }}</td><td class="method">{{ $method }}</td></tr>
            @endif
            @if ($mi('hdd') > 0)
                <tr><td>{{ $L['hdd'] }}</td><td>{{ $mi('hdd') }} {{ $L['items'] }}</td><td class="method">{{ $method }}</td></tr>
            @endif
            @if ($mi('ssd') > 0)
                <tr><td>{{ $L['ssd'] }}</td><td>{{ $mi('ssd') }} {{ $L['items'] }}</td><td class="method">{{ $method }}</td></tr>
            @endif
            @if ($mi('phone') > 0)
                <tr><td>{{ $L['phone'] }}</td><td>{{ $mi('phone') }} {{ $L['items'] }}</td><td class="method">{{ $method }}</td></tr>
            @endif
            @if ($mi('usb') > 0)
                <tr><td>{{ $L['usb'] }}</td><td>{{ $mi('usb') }} {{ $L['items'] }}</td><td class="method">{{ $method }}</td></tr>
            @endif
            @if ($mi('laptop') > 0)
                <tr><td>{{ $L['laptop'] }}</td><td>{{ $mi('laptop') }} {{ $L['items'] }}</td><td class="method">{{ $method }}</td></tr>
            @endif
        </tbody>
    </table>

    <div class="row" style="margin-top:3mm;"><span class="k">{{ $L['loc'] }}</span><span class="v">{{ $locOnsite ? $L['onsite'] : $L['depot'] }}</span></div>

    <p class="confirm">{{ $L['confirm'] }}</p>

    <table class="signs">
        <tr>
            <td>
                <div class="sig-box">
                    <h4>{{ $L['operator'] }}</h4>
                    <div class="sub">{{ $L['name'] }}{{ $L['sep'] }} {{ $certificate->operator_name ?? '' }}</div>
                    <div class="sub">{{ $L['date'] }}{{ $L['sep'] }} {{ $certificate->destroyed_at?->format('d-m-Y') ?? '' }}</div>
                    <div class="sub">{{ $L['signature'] }}{{ $L['sep'] }}</div>
                    <div class="line">@if ($opSig)<img src="{{ $opSig }}" alt="operator signature">@endif</div>
                </div>
            </td>
            <td>
                <div class="sig-box">
                    <h4>{{ $L['witness'] }}</h4>
                    <div class="sub">{{ $L['name'] }}{{ $L['sep'] }} {{ $order->customer_name }}</div>
                    <div class="sub">{{ $L['date'] }}{{ $L['sep'] }} {{ $bon?->picked_up_at?->format('d-m-Y') ?? '' }}</div>
                    <div class="sub">{{ $L['signature'] }}{{ $L['sep'] }}</div>
                    <div class="line">@if ($custSig)<img src="{{ $custSig }}" alt="customer signature">@endif</div>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="trust">
    {{ $co['name'] }} &middot; {{ $L['tagline'] }}<br>
    {{ $co['email'] }} &middot; {{ $co['website'] }} &middot; KvK {{ $co['kvk'] ?: '—' }} &middot; BTW {{ $co['btw'] ?: '—' }}<br>
    {{ $L['badges'] }}
</div>

</body>
</html>
