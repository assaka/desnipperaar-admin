@component('emails.es._layout', ['title' => 'Elija su franja de recogida '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Elija su franja de recogida.</h1>

<p>Hola {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Estamos listos para recoger su pedido
<strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
Usted decide cuándo le viene bien.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:22px 0;">
    <tr>
        <td align="center">
            <a href="{{ $planUrl }}"
               style="display:inline-block;background:#0A0A0A;color:#F5C518;font-weight:900;text-transform:uppercase;letter-spacing:0.06em;font-size:14px;padding:14px 28px;text-decoration:none;">
                Planificar la recogida
            </a>
        </td>
    </tr>
</table>

<p style="font-size:13px;color:#555;">En esa página aparecen las próximas franjas en las que podemos pasar, cada una de una hora. Elige una y queda fijada al momento.</p>

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Dirección</h2>
<div style="font-size:14px;line-height:1.5;">
    @if ($order->customer?->company) <strong>{{ $order->customer->company }}</strong><br> @endif
    {{ $order->customer_name }}<br>
    @if ($order->customer_address) {{ $order->customer_address }}<br> @endif
    <span style="font-family:'Courier New',monospace;">{{ $order->customer_postcode }}</span> {{ $order->customer_city }}
</div>

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Qué debe preparar</h2>
@php
    $mediaLabels = ['hdd' => 'HDD / disco duro', 'ssd' => 'SSD / NVMe', 'usb' => 'Memoria USB / SD', 'phone' => 'Teléfono / tableta', 'laptop' => 'Portátil'];
@endphp
<ul style="font-size:14px;padding-left:20px;">
    @if ($order->box_count) <li>{{ $order->box_count }} {{ $order->box_count == 1 ? 'caja' : 'cajas' }} de papel o expedientes</li> @endif
    @if ($order->container_count) <li>{{ $order->container_count }} {{ $order->container_count == 1 ? 'contenedor' : 'contenedores' }} de 240 L</li> @endif
    @foreach ($mediaLabels as $key => $label)
        @if (!empty($order->media_items[$key]))
            <li>{{ (int) $order->media_items[$key] }}× {{ $label }}</li>
        @endif
    @endforeach
</ul>

<p style="font-size:13px;color:#555;margin-top:20px;">
    ¿No le encaja ninguna? Llame al <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a> y buscamos un momento juntos.
</p>

<p>Hasta pronto.<br>El equipo DeSnipperaar</p>
@endcomponent
