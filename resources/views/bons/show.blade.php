@extends('layouts.app')
@section('title', $bon->bon_number)

@php
    // Render the whole bon page in the order's language (the customer signs here).
    $locale = in_array($bon->order->locale, ['nl', 'en', 'fr', 'es'], true) ? $bon->order->locale : 'nl';

    $svc = [
        'nl' => ['ophaal' => 'Ophaalservice', 'breng' => 'Brengservice'],
        'en' => ['ophaal' => 'Pickup service', 'breng' => 'Drop-off service'],
        'fr' => ['ophaal' => 'Service d\'enlèvement', 'breng' => 'Service de dépôt'],
        'es' => ['ophaal' => 'Servicio de recogida', 'breng' => 'Servicio de entrega'],
    ][$locale];

    $win = [
        'nl' => ['ochtend' => 'Ochtend', 'middag' => 'Middag', 'avond' => 'Avond'],
        'en' => ['ochtend' => 'Morning', 'middag' => 'Afternoon', 'avond' => 'Evening'],
        'fr' => ['ochtend' => 'Matin', 'middag' => 'Après-midi', 'avond' => 'Soir'],
        'es' => ['ochtend' => 'Mañana', 'middag' => 'Tarde', 'avond' => 'Noche'],
    ][$locale];

    // Line-item labels keyed by the Dutch source label produced by Pricing::quote().
    $lineLabels = [
        'Eerste doos'                => ['nl' => 'Eerste doos', 'en' => 'First box', 'fr' => 'Première boîte', 'es' => 'Primera caja'],
        'Volgende dozen'             => ['nl' => 'Volgende dozen', 'en' => 'Additional boxes', 'fr' => 'Boîtes suivantes', 'es' => 'Cajas adicionales'],
        'Kennismaking — eerste doos' => ['nl' => 'Kennismaking · eerste doos', 'en' => 'Introductory · first box', 'fr' => 'Découverte · première boîte', 'es' => 'Bienvenida · primera caja'],
        'Daarna eerste doos'         => ['nl' => 'Daarna eerste doos', 'en' => 'Then first box', 'fr' => 'Puis première boîte', 'es' => 'Luego primera caja'],
        'Eerste rolcontainer 240 L'  => ['nl' => 'Eerste rolcontainer 240 L', 'en' => 'First roll container 240 L', 'fr' => 'Premier conteneur roulant 240 L', 'es' => 'Primer contenedor con ruedas 240 L'],
        'Volgende rolcontainers'     => ['nl' => 'Volgende rolcontainers', 'en' => 'Additional roll containers', 'fr' => 'Conteneurs roulants suivants', 'es' => 'Contenedores con ruedas adicionales'],
        'HDD'                        => ['nl' => 'HDD', 'en' => 'HDD', 'fr' => 'HDD', 'es' => 'HDD'],
        'SSD / NVMe'                 => ['nl' => 'SSD / NVMe', 'en' => 'SSD / NVMe', 'fr' => 'SSD / NVMe', 'es' => 'SSD / NVMe'],
        'USB / SD'                   => ['nl' => 'USB / SD', 'en' => 'USB / SD', 'fr' => 'USB / SD', 'es' => 'USB / SD'],
        'Telefoon / tablet'          => ['nl' => 'Telefoon / tablet', 'en' => 'Phone / tablet', 'fr' => 'Téléphone / tablette', 'es' => 'Teléfono / tableta'],
        'Laptop'                     => ['nl' => 'Laptop', 'en' => 'Laptop', 'fr' => 'Laptop', 'es' => 'Laptop'],
    ];
    $ll = fn ($label) => $lineLabels[$label][$locale] ?? $label;

    $T = [
        'nl' => [
            'signed' => 'getekend', 'not_signed' => 'nog niet getekend', 'order_link' => '← order',
            'customer' => 'Klant', 'pickup_moment' => 'Ophaalmoment', 'flexible' => 'flexibel',
            'locked_pre' => 'Bon is bevestigd &amp; getekend op', 'locked_post' => 'Alle velden zijn vergrendeld voor audit-integriteit.',
            'view_pdf' => 'Bekijk PDF', 'driver' => 'Chauffeur', 'no_driver' => 'Nog geen chauffeur',
            'actual_collected' => 'Werkelijk opgehaald',
            'diff_warning' => 'Werkelijk opgehaald wijkt af van bestelling. Deze aantallen staan op de factuur.',
            'adjust_hint' => 'Pas aan als de klant meer of minder aanbood dan besteld. Besteld is voorgevuld.',
            'boxes' => 'Dozen', 'roll_containers' => 'Rolcontainers', 'ordered' => 'besteld',
            'overview' => 'Overzicht', 'original' => 'Origineel', 'based_on_order' => 'op basis van bestelling',
            'corrected' => 'Gecorrigeerd', 'based_on_actual' => 'op basis van werkelijk opgehaald (dit wordt gefactureerd)',
            'subtotal' => 'Subtotaal', 'subtotal_excl' => 'Subtotaal excl. korting',
            'discount_intro' => 'Korting kennismaking', 'discount_pilot' => 'Korting Amsterdam-pilot',
            'vat' => 'BTW 21%', 'total_incl' => 'Totaal incl. BTW', 'difference' => 'Verschil',
            'seal_numbers' => 'Zegelnummers', 'seal_hint' => 'Eén per regel of gescheiden door komma\'s.',
            'notes' => 'Notities', 'signatures' => 'Handtekeningen', 'signed_check' => '✓ Getekend',
            'sign_again' => 'Opnieuw tekenen', 'clear' => 'Wissen', 'not_assigned' => 'nog niet toegewezen',
            'sign_note' => 'Zodra de klant tekent wordt de ophaaldatum automatisch vastgelegd en gaat de getekende bon als PDF naar de klant. De chauffeur-handtekening wordt automatisch ingevuld vanuit het chauffeur-profiel.',
            'confirm_email' => 'Bevestig &amp; mailen', 'more_than' => ' meer dan besteld.', 'less_than' => ' minder dan besteld.',
        ],
        'en' => [
            'signed' => 'signed', 'not_signed' => 'not signed yet', 'order_link' => '← order',
            'customer' => 'Customer', 'pickup_moment' => 'Pickup', 'flexible' => 'flexible',
            'locked_pre' => 'Bon confirmed &amp; signed on', 'locked_post' => 'All fields are locked for audit integrity.',
            'view_pdf' => 'View PDF', 'driver' => 'Driver', 'no_driver' => 'No driver yet',
            'actual_collected' => 'Actually collected',
            'diff_warning' => 'Actually collected differs from the order. These amounts go on the invoice.',
            'adjust_hint' => 'Adjust if the customer offered more or less than ordered. Ordered is pre-filled.',
            'boxes' => 'Boxes', 'roll_containers' => 'Roll containers', 'ordered' => 'ordered',
            'overview' => 'Overview', 'original' => 'Original', 'based_on_order' => 'based on the order',
            'corrected' => 'Corrected', 'based_on_actual' => 'based on what was actually collected (this is invoiced)',
            'subtotal' => 'Subtotal', 'subtotal_excl' => 'Subtotal excl. discount',
            'discount_intro' => 'Introductory discount', 'discount_pilot' => 'Amsterdam pilot discount',
            'vat' => 'VAT 21%', 'total_incl' => 'Total incl. VAT', 'difference' => 'Difference',
            'seal_numbers' => 'Seal numbers', 'seal_hint' => 'One per line or separated by commas.',
            'notes' => 'Notes', 'signatures' => 'Signatures', 'signed_check' => '✓ Signed',
            'sign_again' => 'Sign again', 'clear' => 'Clear', 'not_assigned' => 'not yet assigned',
            'sign_note' => 'Once the customer signs, the pickup date is recorded automatically and the signed bon is sent as a PDF to the customer. The driver signature is filled in automatically from the driver profile.',
            'confirm_email' => 'Confirm &amp; email', 'more_than' => ' more than ordered.', 'less_than' => ' less than ordered.',
        ],
        'fr' => [
            'signed' => 'signé', 'not_signed' => 'pas encore signé', 'order_link' => '← commande',
            'customer' => 'Client', 'pickup_moment' => 'Enlèvement', 'flexible' => 'flexible',
            'locked_pre' => 'Bon confirmé &amp; signé le', 'locked_post' => 'Tous les champs sont verrouillés pour l\'intégrité de l\'audit.',
            'view_pdf' => 'Voir le PDF', 'driver' => 'Chauffeur', 'no_driver' => 'Pas encore de chauffeur',
            'actual_collected' => 'Réellement collecté',
            'diff_warning' => 'Le réellement collecté diffère de la commande. Ces quantités figurent sur la facture.',
            'adjust_hint' => 'Ajustez si le client a proposé plus ou moins que commandé. La commande est pré-remplie.',
            'boxes' => 'Boîtes', 'roll_containers' => 'Conteneurs roulants', 'ordered' => 'commandé',
            'overview' => 'Aperçu', 'original' => 'Original', 'based_on_order' => 'd\'après la commande',
            'corrected' => 'Corrigé', 'based_on_actual' => 'd\'après le réellement collecté (c\'est ce qui est facturé)',
            'subtotal' => 'Sous-total', 'subtotal_excl' => 'Sous-total hors remise',
            'discount_intro' => 'Remise découverte', 'discount_pilot' => 'Remise pilote Amsterdam',
            'vat' => 'TVA 21%', 'total_incl' => 'Total TTC', 'difference' => 'Différence',
            'seal_numbers' => 'Numéros de scellés', 'seal_hint' => 'Un par ligne ou séparés par des virgules.',
            'notes' => 'Notes', 'signatures' => 'Signatures', 'signed_check' => '✓ Signé',
            'sign_again' => 'Signer à nouveau', 'clear' => 'Effacer', 'not_assigned' => 'pas encore attribué',
            'sign_note' => 'Dès que le client signe, la date d\'enlèvement est enregistrée automatiquement et le bon signé est envoyé en PDF au client. La signature du chauffeur est renseignée automatiquement depuis le profil du chauffeur.',
            'confirm_email' => 'Confirmer &amp; envoyer', 'more_than' => ' de plus que commandé.', 'less_than' => ' de moins que commandé.',
        ],
        'es' => [
            'signed' => 'firmado', 'not_signed' => 'aún sin firmar', 'order_link' => '← pedido',
            'customer' => 'Cliente', 'pickup_moment' => 'Recogida', 'flexible' => 'flexible',
            'locked_pre' => 'Bon confirmado &amp; firmado el', 'locked_post' => 'Todos los campos están bloqueados por integridad de auditoría.',
            'view_pdf' => 'Ver PDF', 'driver' => 'Conductor', 'no_driver' => 'Sin conductor aún',
            'actual_collected' => 'Realmente recogido',
            'diff_warning' => 'Lo realmente recogido difiere del pedido. Estas cantidades aparecen en la factura.',
            'adjust_hint' => 'Ajuste si el cliente ofreció más o menos de lo pedido. El pedido viene rellenado.',
            'boxes' => 'Cajas', 'roll_containers' => 'Contenedores con ruedas', 'ordered' => 'pedido',
            'overview' => 'Resumen', 'original' => 'Original', 'based_on_order' => 'según el pedido',
            'corrected' => 'Corregido', 'based_on_actual' => 'según lo realmente recogido (esto se factura)',
            'subtotal' => 'Subtotal', 'subtotal_excl' => 'Subtotal sin descuento',
            'discount_intro' => 'Descuento de bienvenida', 'discount_pilot' => 'Descuento piloto Ámsterdam',
            'vat' => 'IVA 21%', 'total_incl' => 'Total con IVA', 'difference' => 'Diferencia',
            'seal_numbers' => 'Números de precinto', 'seal_hint' => 'Uno por línea o separados por comas.',
            'notes' => 'Notas', 'signatures' => 'Firmas', 'signed_check' => '✓ Firmado',
            'sign_again' => 'Firmar de nuevo', 'clear' => 'Borrar', 'not_assigned' => 'aún sin asignar',
            'sign_note' => 'En cuanto el cliente firma, la fecha de recogida se registra automáticamente y el bon firmado se envía en PDF al cliente. La firma del conductor se rellena automáticamente desde el perfil del conductor.',
            'confirm_email' => 'Confirmar &amp; enviar', 'more_than' => ' más de lo pedido.', 'less_than' => ' menos de lo pedido.',
        ],
    ][$locale];

    // Labels the Alpine live-quote rebuilds client-side.
    $jsL = [
        'eersteDoos'    => $ll('Eerste doos'),
        'volgendeDozen' => $ll('Volgende dozen'),
        'kennismaking'  => $ll('Kennismaking — eerste doos'),
        'daarnaEerste'  => $ll('Daarna eerste doos'),
        'eersteCont'    => $ll('Eerste rolcontainer 240 L'),
        'volgendeCont'  => $ll('Volgende rolcontainers'),
        'media'         => ['hdd' => 'HDD', 'ssd' => 'SSD / NVMe', 'usb' => 'USB / SD', 'phone' => $ll('Telefoon / tablet'), 'laptop' => 'Laptop'],
        'subtotaal'     => $T['subtotal'],
        'subtotaalExcl' => $T['subtotal_excl'],
        'meer'          => $T['more_than'],
        'minder'        => $T['less_than'],
    ];
@endphp

@section('content')
    <div class="flex justify-between items-baseline mb-4">
        <div>
            <h1 class="text-2xl font-black font-mono">{{ $bon->bon_number }}</h1>
            <div class="text-sm text-gray-600">
                {{ $svc[$bon->mode] ?? (ucfirst($bon->mode) . 'service') }} ·
                <a href="{{ route('orders.show', $bon->order) }}" class="underline">{{ $bon->order->order_number }}</a>
                @if ($bon->picked_up_at)
                    · <span class="bg-green-700 text-white px-2 py-0.5 text-xs font-bold uppercase">{{ $T['signed'] }} {{ $bon->picked_up_at->format('Y-m-d H:i') }}</span>
                @else
                    · <span class="bg-gray-600 text-white px-2 py-0.5 text-xs font-bold uppercase">{{ $T['not_signed'] }}</span>
                @endif
            </div>
        </div>
        <a href="{{ route('orders.show', $bon->order) }}" class="text-sm underline">{{ $T['order_link'] }}</a>
    </div>

    @if (session('warning'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-3 py-2 mb-4 text-sm">{{ session('warning') }}</div>
    @endif

    <section class="grid grid-cols-3 gap-6 mb-6">
        <div>
            <h2 class="font-black mb-2">{{ $T['customer'] }}</h2>
            <div>{{ $bon->order->customer_name }}</div>
            <div class="text-sm">{{ $bon->order->customer_address }}<br>{{ $bon->order->customer_postcode }} {{ $bon->order->customer_city }}</div>
        </div>
        <div>
            <h2 class="font-black mb-2">{{ $T['pickup_moment'] }}</h2>
            @if ($bon->order->pickup_date)
                <div>{{ ucfirst($bon->order->pickup_date->locale($locale)->translatedFormat('l d F Y')) }}</div>
                @php $pw = $bon->order->pickup_window; @endphp
                <div class="text-sm">{{ $pw ? ($win[$pw] ?? ucfirst($pw)) : $T['flexible'] }}@switch($pw)@case('ochtend') · 08:00–12:00 @break @case('middag') · 12:00–17:00 @break @case('avond') · 17:00–20:00 @break @endswitch</div>
            @else
                <div class="text-sm text-gray-500">—</div>
            @endif
        </div>
        <div class="text-right">
            <a href="{{ $publicPdfUrl }}" target="_blank" title="PDF">
                <img src="{{ $qrDataUri }}" alt="QR — PDF" style="width:120px;height:120px;display:inline-block;">
            </a>
        </div>
    </section>

    @php $locked = $bon->picked_up_at && $bon->customer_signature_path; @endphp

    @if ($locked)
        <div class="bg-green-100 border border-green-600 text-green-900 px-3 py-3 mb-4 flex justify-between items-center">
            <div class="text-sm">
                <strong>{!! $T['locked_pre'] !!} {{ $bon->picked_up_at->format('d-m-Y H:i') }}.</strong>
                {{ $T['locked_post'] }}
            </div>
            <a href="{{ route('bons.pdf', $bon) }}" target="_blank" class="bg-black text-yellow-400 px-3 py-2 text-xs uppercase font-bold no-underline">{{ $T['view_pdf'] }}</a>
        </div>
    @endif

    @php
        $expMedia  = $bon->order->media_items ?? [];
        $actMedia  = !empty($bon->actual_media) ? $bon->actual_media : $expMedia;
        $mediaKeys = ['hdd','ssd','usb','phone','laptop'];
    @endphp

    <div x-data="{
        L: {{ \Illuminate\Support\Js::from($jsL) }},
        expBoxes: {{ $bon->order->box_count }},
        expCont:  {{ $bon->order->container_count }},
        expMedia: {{ \Illuminate\Support\Js::from(array_map('intval', $expMedia ?: []) + array_fill_keys($mediaKeys, 0)) }},
        actBoxes: {{ old('actual_boxes', $bon->actual_boxes ?? $bon->order->box_count) ?: 0 }},
        actCont:  {{ old('actual_containers', $bon->actual_containers ?? $bon->order->container_count) ?: 0 }},
        actMedia: {{ \Illuminate\Support\Js::from(array_map('intval', $actMedia ?: []) + array_fill_keys($mediaKeys, 0)) }},
        pilot: {{ $bon->order->pilot ? 'true' : 'false' }},
        firstBoxFree: {{ $bon->order->first_box_free ? 'true' : 'false' }},
        orderedTotal: {{ $orderedQuote['total'] }},
        get diff() {
            if ((this.actBoxes|0) !== this.expBoxes) return true;
            if ((this.actCont|0)  !== this.expCont)  return true;
            for (const k of Object.keys(this.expMedia)) {
                if ((this.actMedia[k]|0) !== (this.expMedia[k]|0)) return true;
            }
            return false;
        },
        get liveQuote() {
            const boxes = this.actBoxes|0, cont = this.actCont|0;
            const bFirst = this.pilot ? 24 : 30, bNext = this.pilot ? 20 : 25;
            const cFirst = this.pilot ? 96 : 120, cNext = this.pilot ? 36 : 45;
            const mPrices = {hdd:9, ssd:15, usb:6, phone:12, laptop:19};
            const mLabels = this.L.media;
            const mk = (label, qty, unit, regularUnit) => {
                const row = {label, qty, unit, subtotal: unit * qty};
                if (regularUnit > unit) {
                    row.was_unit     = regularUnit;
                    row.was_subtotal = regularUnit * qty;
                }
                return row;
            };
            const lines = [];
            if (boxes > 0) {
                if (this.firstBoxFree) {
                    lines.push(mk(this.L.kennismaking, 1, 0, 30));
                    if (boxes >= 2) lines.push(mk(this.L.daarnaEerste, 1, bFirst, 30));
                    if (boxes >= 3) lines.push(mk(this.L.volgendeDozen, boxes-2, bNext, 25));
                } else {
                    lines.push(mk(this.L.eersteDoos, 1, bFirst, 30));
                    if (boxes >= 2) lines.push(mk(this.L.volgendeDozen, boxes-1, bNext, 25));
                }
            }
            if (cont > 0) {
                lines.push(mk(this.L.eersteCont, 1, cFirst, 120));
                if (cont >= 2) lines.push(mk(this.L.volgendeCont, cont-1, cNext, 45));
            }
            for (const k of Object.keys(mPrices)) {
                const q = this.actMedia[k]|0;
                if (q > 0) lines.push(mk(mLabels[k], q, mPrices[k], mPrices[k]));
            }
            const subtotal             = Math.round(lines.reduce((s,l)=>s+l.subtotal,0) * 100) / 100;
            const subtotalRegular      = Math.round(lines.reduce((s,l)=>s+(l.was_subtotal ?? l.subtotal),0) * 100) / 100;
            const discount             = Math.round((subtotalRegular - subtotal) * 100) / 100;
            const discountKennismaking = Math.round(lines.filter(l=>l.unit===0&&l.was_subtotal).reduce((s,l)=>s+l.was_subtotal,0) * 100) / 100;
            const discountPilot        = Math.round((discount - discountKennismaking) * 100) / 100;
            const vat             = Math.round(subtotal * 0.21 * 100) / 100;
            const total           = Math.round((subtotal + vat) * 100) / 100;
            return {lines, subtotal, subtotalRegular, discount, discountKennismaking, discountPilot, vat, total};
        },
        fmt(n) { return '€ ' + Number(n).toFixed(2).replace('.', ','); },
    }">
    <form method="POST" action="{{ route('bons.update', $bon) }}" class="space-y-4"
          {{ $locked ? 'onsubmit=return false' : '' }}>
        @csrf @method('PATCH')
        <fieldset {{ $locked ? 'disabled' : '' }} class="space-y-4 {{ $locked ? 'opacity-60 pointer-events-none' : '' }}">

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 text-sm">
                @foreach ($errors->all() as $err) <div>{{ $err }}</div> @endforeach
            </div>
        @endif

        <section>
            <h2 class="font-black mb-3">{{ $T['driver'] }}</h2>
            <select name="driver_id" class="w-full border p-2">
                <option value="">{{ $T['no_driver'] }}</option>
                @foreach ($drivers as $driver)
                    <option value="{{ $driver->id }}" {{ $bon->driver_id == $driver->id ? 'selected' : '' }}>
                        {{ $driver->name }} (****{{ $driver->license_last4 }})
                    </option>
                @endforeach
            </select>
        </section>

        <section>
            <h2 class="font-black mb-3">{{ $T['actual_collected'] }}</h2>
            <div x-show="diff" x-cloak class="bg-orange-100 border-l-4 border-orange-500 text-orange-900 px-3 py-2 mb-3 font-bold text-sm flex items-center gap-2">
                <span style="font-size:18px;">⚠</span>
                <span>{{ $T['diff_warning'] }}</span>
            </div>
            <p class="text-xs text-gray-500 mb-2">{{ $T['adjust_hint'] }}</p>
            @php
                $mediaCatalog = ['hdd'=>['label'=>'HDD','price'=>9], 'ssd'=>['label'=>'SSD / NVMe','price'=>15], 'usb'=>['label'=>'USB / SD','price'=>6], 'phone'=>['label'=>'Telefoon / tablet','price'=>12], 'laptop'=>['label'=>'Laptop','price'=>19]];
                $actualMedia = !empty($bon->actual_media) ? $bon->actual_media : ($bon->order->media_items ?? []);
            @endphp
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold">{{ $T['boxes'] }} <span class="text-xs font-normal text-gray-500">({{ $T['ordered'] }}: {{ $bon->order->box_count }})</span></label>
                    <input type="number" min="0" name="actual_boxes" x-model.number="actBoxes"
                           :class="actBoxes !== expBoxes ? 'border-orange-500 border-2 bg-orange-50' : ''"
                           class="w-full border p-2">
                </div>
                <div>
                    <label class="block text-sm font-bold">{{ $T['roll_containers'] }} <span class="text-xs font-normal text-gray-500">({{ $T['ordered'] }}: {{ $bon->order->container_count }})</span></label>
                    <input type="number" min="0" name="actual_containers" x-model.number="actCont"
                           :class="actCont !== expCont ? 'border-orange-500 border-2 bg-orange-50' : ''"
                           class="w-full border p-2">
                </div>
            </div>
            <div class="grid grid-cols-5 gap-2 mt-3">
                @foreach ($mediaCatalog as $key => $item)
                    <div>
                        <label class="block text-xs font-bold">{{ $ll($item['label']) }} <span class="text-gray-500">({{ $bon->order->media_items[$key] ?? 0 }})</span></label>
                        <input type="number" min="0" name="actual_media[{{ $key }}]"
                               x-model.number="actMedia.{{ $key }}"
                               :class="actMedia.{{ $key }} !== expMedia.{{ $key }} ? 'border-orange-500 border-2 bg-orange-50' : ''"
                               class="w-full border p-1 text-sm">
                    </div>
                @endforeach
            </div>
        </section>

        @if (count($orderedQuote['lines']))
        <h2 class="font-black mb-3">{{ $T['overview'] }}</h2>
        <section class="mb-6 bg-gray-50 border-l-4 border-yellow-400 p-4">
            <h3 class="font-black mb-2">{{ $T['original'] }}
                <span x-show="diff" x-cloak class="text-xs font-normal text-gray-500">· {{ $T['based_on_order'] }}</span>
            </h3>
            <table class="w-full text-sm">
                @foreach ($orderedQuote['lines'] as $line)
                    <tr class="border-b">
                        <td class="py-1">{{ $ll($line['label']) }}</td>
                        <td class="text-right font-mono">
                            {{ $line['qty'] }} × € {{ number_format($line['unit'], 2, ',', '.') }}
                            @if (!empty($line['was_unit']))
                                <span class="line-through text-gray-400 ml-1">€ {{ number_format($line['was_unit'], 2, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="text-right font-bold font-mono">
                            € {{ number_format($line['was_subtotal'] ?? $line['subtotal'], 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
                <tr><td class="pt-2 text-gray-600">{{ (!empty($orderedQuote['discount']) && $orderedQuote['discount'] > 0) ? $T['subtotal_excl'] : $T['subtotal'] }}</td><td></td>
                    <td class="text-right font-mono pt-2">€ {{ number_format($orderedQuote['subtotal_regular'] ?? $orderedQuote['subtotal'], 2, ',', '.') }}</td></tr>
                @if (!empty($orderedQuote['discount_kennismaking']) && $orderedQuote['discount_kennismaking'] > 0)
                    <tr><td class="text-green-700">{{ $T['discount_intro'] }}</td><td></td>
                        <td class="text-right font-mono text-green-700">− € {{ number_format($orderedQuote['discount_kennismaking'], 2, ',', '.') }}</td></tr>
                @endif
                @if (!empty($orderedQuote['discount_pilot']) && $orderedQuote['discount_pilot'] > 0)
                    <tr><td class="text-green-700">{{ $T['discount_pilot'] }}</td><td></td>
                        <td class="text-right font-mono text-green-700">− € {{ number_format($orderedQuote['discount_pilot'], 2, ',', '.') }}</td></tr>
                @endif
                <tr><td class="text-gray-600">{{ $T['vat'] }}</td><td></td>
                    <td class="text-right font-mono">€ {{ number_format($orderedQuote['vat'], 2, ',', '.') }}</td></tr>
                <tr class="border-t-2 border-black">
                    <td class="pt-2 font-bold">{{ $T['total_incl'] }}</td><td></td>
                    <td class="pt-2 text-right font-bold text-lg font-mono">€ {{ number_format($orderedQuote['total'], 2, ',', '.') }}</td>
                </tr>
            </table>
        </section>

        <section x-show="diff" x-cloak class="mb-6 bg-orange-50 border-l-4 border-orange-500 p-4">
            <h3 class="font-black mb-2 flex items-center gap-2">
                <span style="color:#E67E22;">⚠</span>
                {{ $T['corrected'] }} <span class="text-xs font-normal text-gray-700">· {{ $T['based_on_actual'] }}</span>
            </h3>
            <table class="w-full text-sm">
                <template x-for="(line, i) in liveQuote.lines" :key="i + ':' + line.label">
                    <tr class="border-b">
                        <td class="py-1" x-text="line.label"></td>
                        <td class="text-right font-mono">
                            <span x-text="fmt(line.subtotal)"></span>
                            <span x-show="line.was_subtotal" class="line-through text-gray-400 ml-1" x-text="fmt(line.was_subtotal)"></span>
                        </td>
                        <td class="text-right font-bold font-mono">
                            <span x-text="fmt(line.was_subtotal ?? line.subtotal)"></span>
                        </td>
                    </tr>
                </template>
                <tr><td class="pt-2 text-gray-600" x-text="liveQuote.discount > 0 ? L.subtotaalExcl : L.subtotaal"></td><td></td>
                    <td class="text-right font-mono pt-2" x-text="fmt(liveQuote.subtotalRegular)"></td></tr>
                <tr x-show="liveQuote.discountKennismaking > 0">
                    <td class="text-green-700">{{ $T['discount_intro'] }}</td><td></td>
                    <td class="text-right font-mono text-green-700" x-text="'− ' + fmt(liveQuote.discountKennismaking)"></td></tr>
                <tr x-show="liveQuote.discountPilot > 0">
                    <td class="text-green-700">{{ $T['discount_pilot'] }}</td><td></td>
                    <td class="text-right font-mono text-green-700" x-text="'− ' + fmt(liveQuote.discountPilot)"></td></tr>
                <tr><td class="text-gray-600">{{ $T['vat'] }}</td><td></td>
                    <td class="text-right font-mono" x-text="fmt(liveQuote.vat)"></td></tr>
                <tr class="border-t-2 border-black">
                    <td class="pt-2 font-bold">{{ $T['total_incl'] }}</td><td></td>
                    <td class="pt-2 text-right font-bold text-lg font-mono" x-text="fmt(liveQuote.total)"></td>
                </tr>
            </table>
            <p class="text-sm mt-3">
                <strong>{{ $T['difference'] }}:</strong>
                <span class="font-mono font-bold"
                      :class="(liveQuote.total - orderedTotal) > 0 ? 'text-red-700' : 'text-green-700'"
                      x-text="((liveQuote.total - orderedTotal) > 0 ? '+' : '') + fmt(liveQuote.total - orderedTotal)"></span>
                <span x-text="(liveQuote.total - orderedTotal) > 0 ? L.meer : L.minder"></span>
            </p>
        </section>
        @endif

        <section>
            <h2 class="font-black mb-3">{{ $T['seal_numbers'] }}</h2>
            <p class="text-xs text-gray-500 mb-2">{{ $T['seal_hint'] }}</p>
            <textarea name="seals" rows="4" class="w-full border p-2 font-mono"
                      placeholder="SEAL-000123&#10;SEAL-000124">{{ old('seals', $bon->seals->pluck('seal_number')->implode("\n")) }}</textarea>
        </section>

        <section>
            <h2 class="font-black mb-3">{{ $T['notes'] }}</h2>
            <textarea name="notes" rows="3" class="w-full border p-2">{{ old('notes', $bon->notes) }}</textarea>
        </section>

        <section>
            <h2 class="font-black mb-3">{{ $T['signatures'] }}</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-2">{{ $T['customer'] }} · {{ $bon->order->customer_name }}</label>
                    @if ($bon->customer_signature_path)
                        <div class="border border-green-600 bg-green-50 p-2 text-center">
                            <img src="{{ route('bons.signature', ['bon' => $bon, 'role' => 'customer']) }}" alt="customer signature" style="max-height:120px;margin:0 auto;display:block;">
                            <div class="text-xs text-green-800 mt-1 font-bold uppercase">{{ $T['signed_check'] }}</div>
                        </div>
                        <button type="button" class="text-xs underline mt-1" onclick="document.getElementById('sig-cust-wrap').style.display='block';this.style.display='none';">{{ $T['sign_again'] }}</button>
                    @endif
                    <div id="sig-cust-wrap" style="{{ $bon->customer_signature_path ? 'display:none;' : '' }}">
                        <canvas id="sig-customer" class="border border-black bg-white" width="400" height="300" style="touch-action:none;width:100%;max-width:400px;"></canvas>
                        <div class="flex gap-2 mt-1">
                            <button type="button" class="text-xs underline" onclick="sigCustomer.clear()">{{ $T['clear'] }}</button>
                        </div>
                    </div>
                    <input type="hidden" name="customer_signature" id="sig-customer-data">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-2">{{ $T['driver'] }} · {{ $bon->driver_name_snapshot ?? $T['not_assigned'] }}</label>
                    @if ($bon->driver_signature_path)
                        <div class="border border-green-600 bg-green-50 p-2 text-center">
                            <img src="{{ route('bons.signature', ['bon' => $bon, 'role' => 'driver']) }}" alt="driver signature" style="max-height:120px;margin:0 auto;display:block;">
                            <div class="text-xs text-green-800 mt-1 font-bold uppercase">{{ $T['signed_check'] }}</div>
                        </div>
                        <button type="button" class="text-xs underline mt-1" onclick="document.getElementById('sig-driv-wrap').style.display='block';this.style.display='none';">{{ $T['sign_again'] }}</button>
                    @endif
                    <div id="sig-driv-wrap" style="{{ $bon->driver_signature_path ? 'display:none;' : '' }}">
                        <canvas id="sig-driver" class="border border-black bg-white" width="400" height="300" style="touch-action:none;width:100%;max-width:400px;"></canvas>
                        <div class="flex gap-2 mt-1">
                            <button type="button" class="text-xs underline" onclick="sigDriver.clear()">{{ $T['clear'] }}</button>
                        </div>
                    </div>
                    <input type="hidden" name="driver_signature" id="sig-driver-data">
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">{{ $T['sign_note'] }}</p>
        </section>

        <div class="border-t pt-4 flex gap-3">
            @if (!$locked)
                <button type="submit" class="bg-black text-yellow-400 px-4 py-2 font-bold uppercase">{{ $T['confirm_email'] }}</button>
            @endif
        </div>
        </fieldset>
    </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4/dist/signature_pad.umd.min.js"></script>
    <script>
    (function () {
        var cCanvas = document.getElementById('sig-customer');
        var dCanvas = document.getElementById('sig-driver');
        if (!cCanvas || !dCanvas) return;

        function fitCanvas(canvas) {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            var rect  = canvas.getBoundingClientRect();
            canvas.width  = Math.round(rect.width  * ratio);
            canvas.height = Math.round(rect.height * ratio);
            canvas.getContext('2d').scale(ratio, ratio);
        }
        fitCanvas(cCanvas);
        fitCanvas(dCanvas);

        window.sigCustomer = new SignaturePad(cCanvas, { penColor: '#0A0A0A', backgroundColor: '#FFFFFF' });
        window.sigDriver   = new SignaturePad(dCanvas, { penColor: '#0A0A0A', backgroundColor: '#FFFFFF' });

        var drawing = false, savedScrollY = 0;

        function lockScroll() {
            if (drawing) return;
            drawing = true;
            savedScrollY = window.scrollY || window.pageYOffset;
            document.body.style.position = 'fixed';
            document.body.style.top      = '-' + savedScrollY + 'px';
            document.body.style.width    = '100%';
        }
        function unlockScroll() {
            if (!drawing) return;
            drawing = false;
            document.body.style.position = '';
            document.body.style.top      = '';
            document.body.style.width    = '';
            window.scrollTo(0, savedScrollY);
        }

        [cCanvas, dCanvas].forEach(function (c) {
            c.addEventListener('touchstart',    function (e) { e.preventDefault(); lockScroll(); }, { passive: false });
            c.addEventListener('touchend',      unlockScroll, { passive: false });
            c.addEventListener('touchcancel',   unlockScroll, { passive: false });
            c.addEventListener('pointerdown',   lockScroll);
            c.addEventListener('pointerup',     unlockScroll);
            c.addEventListener('pointercancel', unlockScroll);
        });
        document.addEventListener('touchmove', function (e) {
            if (drawing) e.preventDefault();
        }, { passive: false });

        var form = cCanvas.closest('form');
        form.addEventListener('submit', function () {
            if (!sigCustomer.isEmpty()) document.getElementById('sig-customer-data').value = sigCustomer.toDataURL('image/png');
            if (!sigDriver.isEmpty())   document.getElementById('sig-driver-data').value   = sigDriver.toDataURL('image/png');
        });
    })();
    </script>
@endsection
