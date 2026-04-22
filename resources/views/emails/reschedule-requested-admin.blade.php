@component('emails._layout', ['title' => 'Wijzigingsverzoek '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">⚠ Klant vraagt om herplanning.</h1>

<p>Klant <strong>{{ $order->customer_name }}</strong>@if ($order->customer?->company) ({{ $order->customer->company }})@endif
heeft online een nieuw ophaalmoment voorgesteld voor opdracht
<strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;">
    <tr>
        <td width="48%" valign="top" style="background:#F7F7F4;padding:14px 16px;border-left:3px solid #999;">
            <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.1em;text-transform:uppercase;color:#555;margin-bottom:6px;">Was</div>
            <div style="font-weight:700;font-size:14pt;line-height:1.2;">
                {{ $order->pickup_date?->locale('nl')->translatedFormat('l d F Y') ?? '—' }}
            </div>
            <div style="font-size:13px;margin-top:2px;">{{ ucfirst($order->pickup_window ?? 'flexibel') }}</div>
        </td>
        <td width="4%">&nbsp;</td>
        <td width="48%" valign="top" style="background:#FFF3E0;padding:14px 16px;border-left:3px solid #E67E22;">
            <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.1em;text-transform:uppercase;color:#8B4513;margin-bottom:6px;">Nieuw voorstel</div>
            <div style="font-weight:900;font-size:14pt;line-height:1.2;">
                {{ $order->reschedule_requested_date->locale('nl')->translatedFormat('l d F Y') }}
            </div>
            <div style="font-size:13px;margin-top:2px;">{{ ucfirst($order->reschedule_requested_window) }}</div>
        </td>
    </tr>
</table>

@if ($order->reschedule_notes)
    <h2 style="font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:20px 0 6px;color:#555;">Toelichting klant</h2>
    <p style="background:#FAFAFA;border-left:3px solid #DDD;padding:10px 14px;font-style:italic;">"{{ $order->reschedule_notes }}"</p>
@endif

<h2 style="font-size:13px;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin:20px 0 6px;color:#555;">Contact</h2>
<div style="font-size:14px;line-height:1.7;">
    {{ $order->customer_name }}<br>
    <a href="mailto:{{ $order->customer_email }}" style="color:#0A0A0A;">{{ $order->customer_email }}</a>
    @if ($order->customer_phone)
        &middot; <a href="tel:{{ preg_replace('/\s+/', '', $order->customer_phone) }}" style="color:#0A0A0A;">{{ $order->customer_phone }}</a>
    @endif
</div>

<p style="margin:28px 0;">
    <a href="{{ route('orders.show', $order) }}"
       style="display:inline-block;background:#0A0A0A;color:#F5C518;padding:14px 28px;font-weight:900;font-size:15px;text-transform:uppercase;letter-spacing:0.05em;text-decoration:none;">
        Open order &amp; bevestig nieuwe datum →
    </a>
</p>

<p style="font-size:12px;color:#555;">De planningsform op de orderpagina is automatisch voorgevuld met het voorstel van de klant. Klik <strong>Planning bijwerken &amp; klant mailen</strong> om te bevestigen — dit wist het verzoek en stuurt een nieuwe bevestigingsmail naar de klant.</p>
@endcomponent
