@component('emails.en._layout', ['title' => 'Pickup '.$order->order_number])
@php
    $windowLabels = ['ochtend' => 'Morning', 'middag' => 'Afternoon', 'avond' => 'Evening'];
    $winLabel = $windowLabels[$order->pickup_window] ?? 'Flexible';
@endphp
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Pickup confirmed.</h1>

<p>Dear {{ explode(' ', $order->customer_name)[0] }},</p>

<p>We have scheduled a pickup for your order
<strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:16px 20px;">
            <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.12em;text-transform:uppercase;color:#555;margin-bottom:6px;">We will be at your door on</div>
            <div style="font-weight:900;font-size:20pt;line-height:1.1;">{{ $order->pickup_date->locale('en')->translatedFormat('l d F Y') }}</div>
            <div style="margin-top:4px;font-size:14px;">
                @if (preg_match('/^\d{2}:00-\d{2}:00$/', (string) $order->pickup_window))
                    Time slot: <strong>{{ str_replace('-', ' – ', $order->pickup_window) }}</strong>
                @else
                    Time of day: <strong>{{ $winLabel }}</strong>
                    @switch($order->pickup_window)
                        @case('ochtend') <span style="color:#555;">(08:00 – 12:00)</span> @break
                        @case('middag')  <span style="color:#555;">(12:00 – 17:00)</span> @break
                        @case('avond')   <span style="color:#555;">(17:00 – 20:00)</span> @break
                        @default
                    @endswitch
                @endif
            </div>
        </td>
    </tr>
</table>

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Address</h2>
<div style="font-size:14px;line-height:1.5;">
    @if ($order->customer?->company) <strong>{{ $order->customer->company }}</strong><br> @endif
    {{ $order->customer_name }}<br>
    @if ($order->customer_address) {{ $order->customer_address }}<br> @endif
    <span style="font-family:'Courier New',monospace;">{{ $order->customer_postcode }}</span> {{ $order->customer_city }}
</div>

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">What to have ready for us</h2>
@php
    $mediaLabels = ['hdd' => 'HDD / hard drive', 'ssd' => 'SSD / NVMe', 'usb' => 'USB stick / SD', 'phone' => 'Phone / tablet', 'laptop' => 'Laptop'];
@endphp
<ul style="font-size:14px;padding-left:20px;">
    @if ($order->box_count) <li>{{ $order->box_count }} {{ $order->box_count == 1 ? 'box' : 'boxes' }} of paper or files</li> @endif
    @if ($order->container_count) <li>{{ $order->container_count }} 240 L roll {{ $order->container_count == 1 ? 'container' : 'containers' }}</li> @endif
    @foreach ($mediaLabels as $key => $label)
        @if (!empty($order->media_items[$key]))
            <li>{{ (int) $order->media_items[$key] }}× {{ $label }}</li>
        @endif
    @endforeach
</ul>

<p style="font-size:13px;color:#555;margin-top:20px;">
    Can't make this date?
    @if ($order->public_token)
        <a href="{{ config('desnipperaar.public_url') }}/herplan/{{ $order->public_token }}" style="color:#0A0A0A;font-weight:700;">Reschedule your pickup online</a>
        or call <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a> — we'll move it.
    @else
        Call <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a> — we'll move it.
    @endif
</p>

<p>See you then.<br>Team DeSnipperaar</p>
@endcomponent
