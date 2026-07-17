@component('emails.es._layout', ['title' => 'Confirmación de pedido '.$order->order_number])
@php
    $labelMap = [
        'Kennismaking — eerste doos' => 'Oferta de bienvenida — primera caja',
        'Daarna eerste doos'         => 'Después primera caja',
        'Volgende dozen'             => 'Cajas adicionales',
        'Eerste doos'                => 'Primera caja',
        'Eerste rolcontainer 240 L'  => 'Primer contenedor con ruedas 240 L',
        'Volgende rolcontainers'     => 'Contenedores con ruedas adicionales',
        'Telefoon / tablet'          => 'Teléfono / tableta',
    ];
    $tr = fn ($label) => $labelMap[$label] ?? $label;
    $modeLabels = ['ophaal' => 'Servicio de recogida', 'breng' => 'Servicio de entrega', 'mobiel' => 'Servicio móvil'];
@endphp
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Gracias por su pedido.</h1>

<p>Hola {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Esta es la confirmación de su pedido. Su número de pedido es
<strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
Le contactaremos en un día laborable para confirmar la recogida.</p>

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Resumen del pedido</h2>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:16px;">
    @foreach ($quote['lines'] as $line)
        <tr>
            <td style="padding:6px 0;color:#333;font-size:13px;border-bottom:1px dashed #DDD;">{{ $tr($line['label']) }}</td>
            <td style="padding:6px 0;color:#666;font-size:12px;border-bottom:1px dashed #DDD;text-align:center;font-family:'Courier New',monospace;white-space:nowrap;">
                {{ $line['qty'] }} &times; € {{ number_format($line['unit'], 2, ',', '.') }}
                @if (!empty($line['was_unit']))
                    <span style="text-decoration:line-through;color:#999;margin-left:4px;">€ {{ number_format($line['was_unit'], 2, ',', '.') }}</span>
                @endif
            </td>
            <td style="padding:6px 0;font-weight:700;font-size:13px;border-bottom:1px dashed #DDD;text-align:right;font-family:'Courier New',monospace;white-space:nowrap;">
                € {{ number_format($line['was_subtotal'] ?? $line['subtotal'], 2, ',', '.') }}
            </td>
        </tr>
    @endforeach

    @foreach ($mediaLines as $line)
        <tr>
            <td style="padding:6px 0;color:#333;font-size:13px;border-bottom:1px dashed #DDD;">{{ $tr($line['label']) }}@if (!empty($line['was_subtotal']))<span style="color:#2E7D32;font-weight:700;">&nbsp;*</span>@endif</td>
            <td style="padding:6px 0;color:#666;font-size:12px;border-bottom:1px dashed #DDD;text-align:center;font-family:'Courier New',monospace;white-space:nowrap;">
                {{ $line['qty'] }} &times; € {{ number_format($line['unit'], 2, ',', '.') }}
                @if (!empty($line['was_unit']))
                    <span style="text-decoration:line-through;color:#999;margin-left:4px;">€ {{ number_format($line['was_unit'], 2, ',', '.') }}</span>
                @endif
            </td>
            <td style="padding:6px 0;font-weight:700;font-size:13px;border-bottom:1px dashed #DDD;text-align:right;font-family:'Courier New',monospace;white-space:nowrap;">
                € {{ number_format($line['subtotal'], 2, ',', '.') }}
            </td>
        </tr>
    @endforeach

    @php
        $discountKennismaking = collect($quote['lines'])->sum(fn ($l) => ($l['unit'] == 0 && isset($l['was_subtotal'])) ? $l['was_subtotal'] : 0);
        $discountStaffel = collect($mediaLines)->sum(fn ($l) => isset($l['was_subtotal']) ? $l['was_subtotal'] - $l['subtotal'] : 0);
        $discountPilot = max(0, round((float)($discount ?? 0) - $discountKennismaking - $discountStaffel, 2));
    @endphp
    <tr>
        <td style="padding:10px 0 4px;color:#555;font-size:12px;" colspan="2">{{ (($discountKennismaking + $discountPilot) > 0) ? 'Subtotal antes del descuento' : 'Subtotal' }} (sin IVA)</td>
        <td style="padding:10px 0 4px;font-family:'Courier New',monospace;text-align:right;font-size:13px;">€ {{ number_format(($subtotalRegular ?? $subtotal) - $discountStaffel, 2, ',', '.') }}</td>
    </tr>
    @if ($discountKennismaking > 0)
        <tr>
            <td style="padding:4px 0;color:#2E7D32;font-size:12px;" colspan="2">Descuento de bienvenida</td>
            <td style="padding:4px 0;font-family:'Courier New',monospace;text-align:right;font-size:13px;color:#2E7D32;">− € {{ number_format($discountKennismaking, 2, ',', '.') }}</td>
        </tr>
    @endif
    @if ($discountPilot > 0)
        <tr>
            <td style="padding:4px 0;color:#2E7D32;font-size:12px;" colspan="2">Descuento piloto Ámsterdam</td>
            <td style="padding:4px 0;font-family:'Courier New',monospace;text-align:right;font-size:13px;color:#2E7D32;">− € {{ number_format($discountPilot, 2, ',', '.') }}</td>
        </tr>
    @endif
    <tr>
        <td style="padding:4px 0;color:#555;font-size:12px;" colspan="2">IVA 21%</td>
        <td style="padding:4px 0;font-family:'Courier New',monospace;text-align:right;font-size:13px;">€ {{ number_format($vat, 2, ',', '.') }}</td>
    </tr>
    <tr>
        <td style="padding:10px 0 4px;font-weight:900;font-size:15px;border-top:2px solid #0A0A0A;" colspan="2">Total con IVA</td>
        <td style="padding:10px 0 4px;font-weight:900;font-size:16px;border-top:2px solid #0A0A0A;text-align:right;font-family:'Courier New',monospace;">€ {{ number_format($total, 2, ',', '.') }}</td>
    </tr>
</table>
@if ($discountStaffel > 0)
    <p style="font-size:11px;color:#777;margin:-8px 0 16px;" class="staffel-note">* Descuento por volumen aplicado y ya incluido en estos precios.</p>
@endif

@if ($order->quote_body)
<div style="font-size:14px;line-height:1.6;background:#F7F7F4;padding:14px;border-left:3px solid #F5C518;margin:16px 0;">{!! nl2br(e($order->quote_body)) !!}</div>
@endif

@if ($order->pilot)
    <p style="background:#F5C518;padding:6px 10px;display:inline-block;font-size:12px;font-weight:700;margin:0 0 16px;">
        ✓ Piloto Ámsterdam · 20% de descuento aplicado
    </p>
@endif
@if ($order->first_box_free)
    <p style="background:#0A0A0A;color:#F5C518;padding:6px 10px;display:inline-block;font-size:12px;font-weight:700;margin:0 0 16px;">
        ✨ Oferta de bienvenida · primera caja gratis
    </p>
@endif

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Dirección de recogida</h2>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:16px;">
    <tr>
        <td style="padding:4px 0;color:#555;font-size:12px;width:140px;">Nombre</td>
        <td style="padding:4px 0;font-weight:700;font-size:13px;">{{ $order->customer_name }}</td>
    </tr>
    @if ($order->customer_address)
        <tr>
            <td style="padding:4px 0;color:#555;font-size:12px;">Dirección</td>
            <td style="padding:4px 0;font-weight:700;font-size:13px;">{{ $order->customer_address }}</td>
        </tr>
    @endif
    <tr>
        <td style="padding:4px 0;color:#555;font-size:12px;">Código postal / ciudad</td>
        <td style="padding:4px 0;font-weight:700;font-size:13px;">
            <span style="font-family:'Courier New',monospace;">{{ $order->customer_postcode }}</span>
            @if ($order->customer_city) &middot; {{ $order->customer_city }} @endif
        </td>
    </tr>
    <tr>
        <td style="padding:4px 0;color:#555;font-size:12px;">Tipo de servicio</td>
        <td style="padding:4px 0;font-weight:700;font-size:13px;">{{ $modeLabels[$order->delivery_mode] ?? ucfirst($order->delivery_mode).' service' }}</td>
    </tr>
    @if ($order->pickup_date)
        <tr>
            <td style="padding:4px 0;color:#555;font-size:12px;">Fecha solicitada</td>
            <td style="padding:4px 0;font-weight:700;font-size:13px;">
                {{ $order->pickup_date->format('d-m-Y') }}
                @if ($order->pickup_window) ({{ $order->pickup_window }}) @endif
            </td>
        </tr>
    @endif
</table>

<p style="font-size:12px;color:#555;">¿La dirección no es correcta? Responda a este correo con los datos correctos.</p>

<p>¿Preguntas? Llame al <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a>
o escriba a <a href="mailto:sales@desnipperaar.nl" style="color:#0A0A0A;">sales@desnipperaar.nl</a>.</p>

<p>Un cordial saludo,<br>El equipo de DeSnipperaar</p>
@endcomponent
