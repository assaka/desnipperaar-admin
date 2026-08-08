@component('emails.en._layout', ['title' => 'Choose your pickup slot '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Choose your own pickup slot.</h1>

<p>Dear {{ explode(' ', $order->customer_name)[0] }},</p>

<p>We are ready to collect your order
<strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
You decide when that suits you.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:22px 0;">
    <tr>
        <td align="center">
            <a href="{{ $planUrl }}"
               style="display:inline-block;background:#0A0A0A;color:#F5C518;font-weight:900;text-transform:uppercase;letter-spacing:0.06em;font-size:14px;padding:14px 28px;text-decoration:none;">
                Plan the pickup
            </a>
        </td>
    </tr>
</table>

<p style="font-size:13px;color:#555;">That page lists the next slots we can drive to you, each with a one-hour window. Pick one and it is set straight away.</p>

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Address</h2>
<div style="font-size:14px;line-height:1.5;">
    @if ($order->customer?->company) <strong>{{ $order->customer->company }}</strong><br> @endif
    {{ $order->customer_name }}<br>
    @if ($order->customer_address) {{ $order->customer_address }}<br> @endif
    <span style="font-family:'Courier New',monospace;">{{ $order->customer_postcode }}</span> {{ $order->customer_city }}
</div>

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">What to have ready</h2>
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
    None of them work? Call <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a> and we will find a slot together.
</p>

<p>See you then.<br>Team DeSnipperaar</p>
@endcomponent
