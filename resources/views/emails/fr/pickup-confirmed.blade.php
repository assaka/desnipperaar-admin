@component('emails.fr._layout', ['title' => 'Enlèvement '.$order->order_number])
@php
    $windowLabels = ['ochtend' => 'Matin', 'middag' => 'Après-midi', 'avond' => 'Soir'];
    $winLabel = $windowLabels[$order->pickup_window] ?? 'Flexible';
@endphp
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Enlèvement confirmé.</h1>

<p>Bonjour {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Nous avons planifié un enlèvement pour votre commande
<strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:16px 20px;">
            <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.12em;text-transform:uppercase;color:#555;margin-bottom:6px;">Nous serons à votre porte le</div>
            <div style="font-weight:900;font-size:20pt;line-height:1.1;">{{ $order->pickup_date->locale('fr')->translatedFormat('l d F Y') }}</div>
            <div style="margin-top:4px;font-size:14px;">
                @if (preg_match('/^\d{2}:00-\d{2}:00$/', (string) $order->pickup_window))
                    Créneau horaire : <strong>{{ str_replace('-', ' – ', $order->pickup_window) }}</strong>
                @else
                    Moment de la journée : <strong>{{ $winLabel }}</strong>
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

@if (!empty($order->pickup_note))
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;background:#FFF8E1;border-left:4px solid #F5C518;">
    <tr><td style="padding:14px 18px;font-size:14px;line-height:1.5;">
        <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.12em;text-transform:uppercase;color:#555;margin-bottom:6px;">Un mot de notre part</div>
        {!! nl2br(e($order->pickup_note)) !!}
    </td></tr>
</table>
@endif

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">Adresse</h2>
<div style="font-size:14px;line-height:1.5;">
    @if ($order->customer?->company) <strong>{{ $order->customer->company }}</strong><br> @endif
    {{ $order->customer_name }}<br>
    @if ($order->customer_address) {{ $order->customer_address }}<br> @endif
    <span style="font-family:'Courier New',monospace;">{{ $order->customer_postcode }}</span> {{ $order->customer_city }}
</div>

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">À préparer pour nous</h2>
@php
    $mediaLabels = ['hdd' => 'HDD / disque dur', 'ssd' => 'SSD / NVMe', 'usb' => 'Clé USB / SD', 'phone' => 'Téléphone / tablette', 'laptop' => 'Ordinateur portable'];
@endphp
<ul style="font-size:14px;padding-left:20px;">
    @if ($order->box_count) <li>{{ $order->box_count }} {{ $order->box_count == 1 ? 'carton' : 'cartons' }} de papier ou de dossiers</li> @endif
    @if ($order->container_count) <li>{{ $order->container_count }} {{ $order->container_count == 1 ? 'conteneur roulant' : 'conteneurs roulants' }} de 240 L</li> @endif
    @foreach ($mediaLabels as $key => $label)
        @if (!empty($order->media_items[$key]))
            <li>{{ (int) $order->media_items[$key] }}× {{ $label }}</li>
        @endif
    @endforeach
</ul>

<p style="font-size:13px;color:#555;margin-top:20px;">
    Cette date ne convient pas ?
    @if ($order->public_token)
        <a href="{{ config('desnipperaar.public_url') }}/herplan/{{ $order->public_token }}" style="color:#0A0A0A;font-weight:700;">Reprogrammez votre enlèvement en ligne</a>
        ou appelez le <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a> — nous le déplaçons.
    @else
        Appelez le <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a> — nous le déplaçons.
    @endif
</p>

<p>À bientôt.<br>L'équipe DeSnipperaar</p>
@endcomponent
