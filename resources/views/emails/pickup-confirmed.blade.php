@component('emails._layout', ['title' => 'Ophaalmoment '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Ophaalmoment bevestigd.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Wij hebben een ophaalmoment vastgesteld voor uw opdracht
<strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:16px 20px;">
            <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.12em;text-transform:uppercase;color:#555;margin-bottom:6px;">Wij staan voor de deur op</div>
            <div style="font-weight:900;font-size:20pt;line-height:1.1;">{{ $order->pickup_date->locale('nl')->translatedFormat('l d F Y') }}</div>
            <div style="margin-top:4px;font-size:14px;">Dagdeel: <strong>{{ ucfirst($order->pickup_window ?? 'flexibel') }}</strong>
                @switch($order->pickup_window)
                    @case('ochtend') <span style="color:#555;">(08:00 – 12:00)</span> @break
                    @case('middag')  <span style="color:#555;">(12:00 – 17:00)</span> @break
                    @case('avond')   <span style="color:#555;">(17:00 – 20:00)</span> @break
                    @default         <span style="color:#555;">(wij bellen 30 min voor aankomst)</span>
                @endswitch
            </div>
        </td>
    </tr>
</table>

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
    Lukt deze datum niet meer?
    @if ($order->public_token)
        <a href="{{ route('reschedule.show', $order->public_token) }}" style="color:#0A0A0A;font-weight:700;">Wijzig online uw ophaalmoment</a>
        of bel <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a> — dan schuiven we het moment.
    @else
        Bel <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a> — dan schuiven we het moment.
    @endif
</p>

<p>Tot dan.<br>Team DeSnipperaar</p>
@endcomponent
