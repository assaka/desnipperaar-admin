@php
    $copy = [
        'nl' => [
            'title' => 'Ophaalmoment definitief',
            'h1' => 'Ophaalmoment definitief bevestigd.',
            'beste' => 'Beste',
            'intro' => 'Bedankt voor uw bevestiging. Het ophaalmoment voor uw opdracht :nr staat hiermee definitief vast.',
            'deur' => 'Wij staan voor de deur op',
            'tijdvak' => 'Tijdvak', 'dagdeel' => 'Dagdeel',
            'adres' => 'Ophaaladres',
            'nietUit' => 'Komt het toch niet uit? Neem dan binnen 24 uur contact met ons op via',
            'of' => 'of', 'tel' => '06-10229965',
            'wa' => 'Ophaalmoment :nr komt toch niet uit. ',
            'slot' => 'Tot dan.<br>Team DeSnipperaar',
        ],
        'en' => [
            'title' => 'Pickup finalised',
            'h1' => 'Your pickup is final.',
            'beste' => 'Dear',
            'intro' => 'Thank you for confirming. The pickup slot for your order :nr is now final.',
            'deur' => 'We will be at your door on',
            'tijdvak' => 'Time slot', 'dagdeel' => 'Part of day',
            'adres' => 'Pickup address',
            'nietUit' => 'Does it not suit you after all? Then please contact us within 24 hours via',
            'of' => 'or', 'tel' => '+31 6 10229965',
            'wa' => 'Pickup :nr does not suit me after all. ',
            'slot' => 'See you then.<br>Team DeSnipperaar',
        ],
        'fr' => [
            'title' => 'Enlèvement définitif',
            'h1' => 'Enlèvement définitivement confirmé.',
            'beste' => 'Bonjour',
            'intro' => 'Merci pour votre confirmation. Le créneau d\'enlèvement de votre commande :nr est désormais définitif.',
            'deur' => 'Nous serons à votre porte le',
            'tijdvak' => 'Créneau horaire', 'dagdeel' => 'Moment de la journée',
            'adres' => 'Adresse d\'enlèvement',
            'nietUit' => 'Finalement, cela ne vous convient pas ? Contactez-nous dans les 24 heures via',
            'of' => 'ou au', 'tel' => '+31 6 10229965',
            'wa' => 'L\'enlèvement :nr ne me convient finalement pas. ',
            'slot' => 'À bientôt.<br>L\'équipe DeSnipperaar',
        ],
        'es' => [
            'title' => 'Recogida definitiva',
            'h1' => 'Recogida confirmada definitivamente.',
            'beste' => 'Estimado/a',
            'intro' => 'Gracias por su confirmación. El momento de recogida de su pedido :nr queda confirmado definitivamente.',
            'deur' => 'Estaremos en su puerta el',
            'tijdvak' => 'Franja horaria', 'dagdeel' => 'Parte del día',
            'adres' => 'Dirección de recogida',
            'nietUit' => '¿Al final no le viene bien? Contáctenos en un plazo de 24 horas por',
            'of' => 'o al', 'tel' => '+31 6 10229965',
            'wa' => 'Al final la recogida :nr no me viene bien. ',
            'slot' => 'Hasta entonces.<br>El equipo de DeSnipperaar',
        ],
    ];
    $t = $copy[$lang] ?? $copy['nl'];
    $layout = $lang === 'nl' ? 'emails._layout' : 'emails.'.$lang.'._layout';
    $nr = '<strong style="font-family:\'Courier New\',monospace;background:#F5C518;padding:2px 6px;">'.e($order->order_number).'</strong>';
    $waUrl = 'https://wa.me/31610229965?text='.rawurlencode(str_replace(':nr', $order->order_number, $t['wa']));
    $ophaalAdres = $order->pickupLocation();
@endphp
@component($layout, ['title' => $t['title'].' '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">{{ $t['h1'] }}</h1>

<p>{{ $t['beste'] }} {{ explode(' ', $order->customer_name)[0] }},</p>

<p>{!! str_replace(':nr', $nr, e($t['intro'])) !!}</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:16px 20px;">
            <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.12em;text-transform:uppercase;color:#555;margin-bottom:6px;">{{ $t['deur'] }}</div>
            <div style="font-weight:900;font-size:20pt;line-height:1.1;">{{ $order->pickup_date->locale($lang)->translatedFormat('l d F Y') }}</div>
            @if ($order->pickup_window)
                <div style="margin-top:4px;font-size:14px;">
                    @if (preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}$/', (string) $order->pickup_window))
                        {{ $t['tijdvak'] }} <strong>{{ str_replace('-', ' – ', $order->pickup_window) }}</strong>
                    @else
                        {{ $t['dagdeel'] }} <strong>{{ ucfirst($order->pickup_window) }}</strong>
                    @endif
                </div>
            @endif
        </td>
    </tr>
</table>

<h2 style="font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:24px 0 10px;border-bottom:2px solid #0A0A0A;padding-bottom:6px;">{{ $t['adres'] }}</h2>
<div style="font-size:14px;line-height:1.5;">
    @if ($order->customer?->company) <strong>{{ $order->customer->company }}</strong><br> @endif
    @if ($ophaalAdres['address']) {{ $ophaalAdres['address'] }}<br> @endif
    <span style="font-family:'Courier New',monospace;">{{ $ophaalAdres['postcode'] }}</span> {{ $ophaalAdres['city'] }}
</div>

<p style="font-size:13px;color:#555;margin-top:24px;">
    {{ $t['nietUit'] }} <a href="{{ $waUrl }}" style="color:#0A0A0A;font-weight:700;">WhatsApp</a> {{ $t['of'] }} <a href="tel:+31610229965" style="color:#0A0A0A;">{{ $t['tel'] }}</a>.
</p>

<p>{!! $t['slot'] !!}</p>
@endcomponent
