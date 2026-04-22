@component('emails._layout', ['title' => 'Wijzigingsverzoek '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Wijzigingsverzoek ontvangen.</h1>

<p>Beste {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Wij hebben uw verzoek tot wijziging van het ophaalmoment ontvangen voor opdracht
<strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:16px 20px;">
            <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.12em;text-transform:uppercase;color:#555;margin-bottom:6px;">Nieuw voorstel</div>
            <div style="font-weight:900;font-size:18pt;line-height:1.1;">
                {{ $order->reschedule_requested_date->locale('nl')->translatedFormat('l d F Y') }}
            </div>
            <div style="margin-top:4px;font-size:14px;">Dagdeel: <strong>{{ ucfirst($order->reschedule_requested_window) }}</strong>
                @switch($order->reschedule_requested_window)
                    @case('ochtend') <span style="color:#555;">(08:00 – 12:00)</span> @break
                    @case('middag')  <span style="color:#555;">(12:00 – 17:00)</span> @break
                    @case('avond')   <span style="color:#555;">(17:00 – 20:00)</span> @break
                    @default         <span style="color:#555;">(wij bellen 30 min voor aankomst)</span>
                @endswitch
            </div>
        </td>
    </tr>
</table>

@if ($order->reschedule_notes)
    <h2 style="font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:20px 0 6px;color:#555;">Toelichting klant</h2>
    <p style="background:#FAFAFA;border-left:3px solid #DDD;padding:10px 14px;font-style:italic;">"{{ $order->reschedule_notes }}"</p>
@endif

<h2 style="font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:20px 0 6px;color:#555;">Oorspronkelijke afspraak</h2>
<p>
    {{ $order->pickup_date?->locale('nl')->translatedFormat('l d F Y') ?? '—' }}
    — {{ ucfirst($order->pickup_window ?? 'flexibel') }}
</p>

<p style="margin-top:20px;">Wij controleren de beschikbaarheid en bevestigen uw nieuwe ophaalmoment per e-mail — meestal binnen één werkdag.</p>

<p style="font-size:13px;color:#555;margin-top:20px;">Nog iets toe te voegen? Reply op deze mail of bel <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a>.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
