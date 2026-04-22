@component('emails._layout', ['title' => 'Ophaalbon '.$bon->bon_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Uw papier is opgehaald.</h1>

<p>Beste {{ explode(' ', $bon->order->customer_name)[0] }},</p>

<p>De ophaling voor order <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong> is zojuist uitgevoerd. Hierbij de getekende ophaalbon als PDF-bijlage.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:14px 18px;font-size:13px;">
            <div><strong>Bonnummer:</strong> <span style="font-family:'Courier New',monospace;">{{ $bon->bon_number }}</span></div>
            <div><strong>Datum:</strong> {{ $bon->picked_up_at?->format('d-m-Y H:i') }}</div>
            @if ($bon->weight_kg) <div><strong>Gewicht:</strong> {{ $bon->weight_kg }} kg</div> @endif
            @if ($bon->seals->count())
                <div><strong>Aantal zegels:</strong> {{ $bon->seals->count() }}</div>
            @endif
            <div><strong>Chauffeur:</strong> {{ $bon->driver_name_snapshot ?? '—' }} (rijbewijs ****{{ $bon->driver_license_last4 ?? '—' }})</div>
        </td>
    </tr>
</table>

<p style="font-size:13px;color:#555;">De zegelnummers en getekende bon zijn uw bewijs dat het materiaal verzegeld is opgehaald. Bewaar deze mail en PDF in uw dossier — zij vormen samen met het nog te volgen <strong>Certificaat van Vernietiging</strong> de complete audit trail.</p>

<p>Het certificaat ontvangt u binnen 24 uur, nadat het materiaal is vernietigd.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
