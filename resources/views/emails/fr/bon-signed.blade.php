@php
    $isBezorg = $bon->mode === 'bezorging';
    $isRetour = $bon->mode === 'retour';
    $isOphaal = ! $isBezorg && ! $isRetour;
    $heading = $isBezorg ? 'Votre conteneur a été livré.' : ($isRetour ? 'Votre conteneur a été récupéré.' : 'Vos documents ont été enlevés.');
    $bonLabel = $isBezorg ? 'Bon de livraison' : ($isRetour ? 'Bon de retour' : "Bon d'enlèvement");
@endphp
@component('emails.fr._layout', ['title' => $bonLabel.' '.$bon->bon_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">{{ $heading }}</h1>

<p>Bonjour {{ explode(' ', $bon->order->customer_name)[0] }},</p>

@if ($isBezorg)
    <p>Nous venons de livrer votre conteneur roulant scellé de 240 L pour l'abonnement
    <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong>.
    Vous trouverez le bon de livraison signé en pièce jointe au format PDF. Vous pouvez le remplir dès maintenant.</p>
@elseif ($isRetour)
    <p>Nous avons récupéré le conteneur pour l'abonnement
    <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong>.
    Vous trouverez le bon de retour signé en pièce jointe au format PDF. Votre abonnement est désormais clôturé.</p>
@else
    <p>L'enlèvement pour la commande <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong>
    vient d'être effectué. Vous trouverez le bon d'enlèvement signé en pièce jointe au format PDF.</p>
@endif

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:14px 18px;font-size:13px;">
            <div><strong>Numéro de bon :</strong> <span style="font-family:'Courier New',monospace;">{{ $bon->bon_number }}</span></div>
            <div><strong>Date :</strong> {{ $bon->picked_up_at?->format('d-m-Y H:i') }}</div>
            @if ($isOphaal && $bon->weight_kg) <div><strong>Poids :</strong> {{ $bon->weight_kg }} kg</div> @endif
            @if ($isOphaal && $bon->seals->count())
                <div><strong>Nombre de scellés :</strong> {{ $bon->seals->count() }}</div>
            @endif
            <div><strong>Chauffeur :</strong> {{ $bon->driver_name_snapshot ?? '—' }} (permis ****{{ $bon->driver_license_last4 ?? '—' }})</div>
        </td>
    </tr>
</table>

@if ($isOphaal)
    <p style="font-size:13px;color:#555;">Les numéros de scellés et le bon signé constituent votre preuve que le matériel a été enlevé scellé. Conservez cet e-mail et le PDF dans vos dossiers — avec le <strong>Certificat de Destruction</strong> qui suit, ils forment la piste d'audit complète.</p>
    <p>Vous recevrez le certificat sous 24 heures, une fois le matériel détruit.</p>
@elseif ($isBezorg)
    <p>Nous collectons périodiquement selon votre abonnement. Vous recevez un rappel la veille de chaque enlèvement, et un certificat de destruction à chaque enlèvement.</p>
@else
    <p>Conservez ce bon signé dans vos dossiers.</p>
@endif

<p>Cordialement,<br>L'équipe DeSnipperaar</p>
@endcomponent
