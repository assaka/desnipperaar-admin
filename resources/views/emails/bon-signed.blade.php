@php
    $isBezorg = $bon->mode === 'bezorging';
    $isRetour = $bon->mode === 'retour';
    $isOphaal = ! $isBezorg && ! $isRetour;
    $heading = $isBezorg ? 'Uw container is bezorgd.' : ($isRetour ? 'Uw container is opgehaald.' : 'Uw papier is opgehaald.');
    $bonLabel = $isBezorg ? 'bezorgbon' : ($isRetour ? 'retourbon' : 'ophaalbon');
@endphp
@component('emails._layout', ['title' => ucfirst($bonLabel).' '.$bon->bon_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">{{ $heading }}</h1>

<p>Beste {{ explode(' ', $bon->order->customer_name)[0] }},</p>

@if ($isBezorg)
    <p>Wij hebben zojuist uw verzegelde 240 L rolcontainer bezorgd voor abonnement
    <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong>.
    Hierbij de getekende bezorgbon als PDF-bijlage. U kunt de container meteen vullen.</p>
@elseif ($isRetour)
    <p>Wij hebben de container weer bij u opgehaald voor abonnement
    <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong>.
    Hierbij de getekende retourbon als PDF-bijlage. Uw abonnement is hiermee afgerond.</p>
@else
    <p>De ophaling voor order <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong>
    is zojuist uitgevoerd. Hierbij de getekende ophaalbon als PDF-bijlage.</p>
@endif

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:14px 18px;font-size:13px;">
            <div><strong>Bonnummer:</strong> <span style="font-family:'Courier New',monospace;">{{ $bon->bon_number }}</span></div>
            <div><strong>Datum:</strong> {{ $bon->picked_up_at?->format('d-m-Y H:i') }}</div>
            @if ($isOphaal && $bon->weight_kg) <div><strong>Gewicht:</strong> {{ $bon->weight_kg }} kg</div> @endif
            @if ($isOphaal && $bon->seals->count())
                <div><strong>Aantal zegels:</strong> {{ $bon->seals->count() }}</div>
            @endif
            <div><strong>Chauffeur:</strong> {{ $bon->driver_name_snapshot ?? '—' }} (rijbewijs ****{{ $bon->driver_license_last4 ?? '—' }})</div>
        </td>
    </tr>
</table>

@if ($isOphaal)
    <p style="font-size:13px;color:#555;">De zegelnummers en getekende bon zijn uw bewijs dat het materiaal verzegeld is opgehaald. Bewaar deze mail en PDF in uw dossier — zij vormen samen met het nog te volgen <strong>Certificaat van Vernietiging</strong> de complete audit trail.</p>
    <p>Het certificaat ontvangt u binnen 24 uur, nadat het materiaal is vernietigd.</p>
@elseif ($isBezorg)
    <p>Wij halen periodiek op volgens uw abonnement. U krijgt telkens een dag voor de ophaling een herinnering, en bij elke ophaling een vernietigingscertificaat.</p>
@else
    <p>Bewaar deze getekende bon in uw dossier.</p>
@endif

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
