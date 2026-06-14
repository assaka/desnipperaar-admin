@component('emails.fr._layout', ['title' => 'Demande de modification '.$order->order_number])
@php
    $windowLabels = ['ochtend' => 'Matin', 'middag' => 'Après-midi', 'avond' => 'Soir'];
@endphp
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Demande de modification reçue.</h1>

<p>Bonjour {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Nous avons bien reçu votre demande de reprogrammation de l'enlèvement pour la commande
<strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:16px 20px;">
            <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.12em;text-transform:uppercase;color:#555;margin-bottom:6px;">Nouvelle proposition</div>
            <div style="font-weight:900;font-size:18pt;line-height:1.1;">
                {{ $order->reschedule_requested_date->locale('fr')->translatedFormat('l d F Y') }}
            </div>
            <div style="margin-top:4px;font-size:14px;">Moment de la journée : <strong>{{ $windowLabels[$order->reschedule_requested_window] ?? 'Flexible' }}</strong>
                @switch($order->reschedule_requested_window)
                    @case('ochtend') <span style="color:#555;">(08:00 – 12:00)</span> @break
                    @case('middag')  <span style="color:#555;">(12:00 – 17:00)</span> @break
                    @case('avond')   <span style="color:#555;">(17:00 – 20:00)</span> @break
                    @default         <span style="color:#555;">(nous appelons 30 min avant l'arrivée)</span>
                @endswitch
            </div>
        </td>
    </tr>
</table>

@if ($order->reschedule_notes)
    <h2 style="font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:20px 0 6px;color:#555;">Note du client</h2>
    <p style="background:#FAFAFA;border-left:3px solid #DDD;padding:10px 14px;font-style:italic;">«&nbsp;{{ $order->reschedule_notes }}&nbsp;»</p>
@endif

<h2 style="font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:20px 0 6px;color:#555;">Rendez-vous initial</h2>
<p>
    {{ $order->pickup_date?->locale('fr')->translatedFormat('l d F Y') ?? '—' }}
    — {{ $windowLabels[$order->pickup_window] ?? 'Flexible' }}
</p>

<p style="margin-top:20px;">Nous vérifions la disponibilité et confirmons votre nouveau créneau d'enlèvement par e-mail — généralement sous un jour ouvré.</p>

<p style="font-size:13px;color:#555;margin-top:20px;">Quelque chose à ajouter ? Répondez à cet e-mail ou appelez le <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a>.</p>

<p>Cordialement,<br>L'équipe DeSnipperaar</p>
@endcomponent
