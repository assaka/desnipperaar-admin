@component('emails._layout', ['title' => 'Ophaalmoment kiezen '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Kies zelf uw ophaalmoment.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Wij zijn klaar om uw opdracht
<strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>
op te halen. U kiest zelf wanneer dat uitkomt.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:22px 0;">
    <tr>
        <td align="center">
            <a href="{{ $planUrl }}"
               style="display:inline-block;background:#0A0A0A;color:#F5C518;font-weight:900;text-transform:uppercase;letter-spacing:0.06em;font-size:14px;padding:14px 28px;text-decoration:none;">
                Plan de ophaling
            </a>
        </td>
    </tr>
</table>

<p style="font-size:13px;color:#555;">Op die pagina staan de eerstvolgende momenten die wij bij u kunnen rijden, met een tijdvak van een uur erbij. U kiest er een en het staat meteen vast.</p>

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Adres</h2>
<div style="font-size:14px;line-height:1.5;">
    @if ($order->customer?->company) <strong>{{ $order->customer->company }}</strong><br> @endif
    {{ $order->customer_name }}<br>
    @if ($order->customer_address) {{ $order->customer_address }}<br> @endif
    <span style="font-family:'Courier New',monospace;">{{ $order->customer_postcode }}</span> {{ $order->customer_city }}
</div>

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Wat u voor ons klaarzet</h2>
@php
    $mediaLabels = ['hdd' => 'HDD / harde schijf', 'ssd' => 'SSD / NVMe', 'usb' => 'USB-stick / SD', 'phone' => 'Telefoon / tablet', 'laptop' => 'Laptop'];
@endphp
<ul style="font-size:14px;padding-left:20px;">
    @if ($order->box_count) <li>{{ $order->box_count }} {{ $order->box_count == 1 ? 'doos' : 'dozen' }} met papier of dossiers</li> @endif
    @if ($order->container_count) <li>{{ $order->container_count }} {{ $order->container_count == 1 ? 'rolcontainer' : 'rolcontainers' }} 240 L</li> @endif
    @foreach ($mediaLabels as $key => $label)
        @if (!empty($order->media_items[$key]))
            <li>{{ (int) $order->media_items[$key] }}× {{ $label }}</li>
        @endif
    @endforeach
</ul>

<p style="font-size:13px;color:#555;margin-top:20px;">
    Past er niets bij? Bel <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a>, dan zoeken wij samen een moment.
</p>

<p>Tot dan.<br>Team DeSnipperaar</p>
@endcomponent
