@component('emails.fr._layout', ['title' => "Bon d'enlèvement ".$bon->bon_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Vos documents ont été enlevés.</h1>

<p>Bonjour {{ explode(' ', $bon->order->customer_name)[0] }},</p>

<p>L'enlèvement pour la commande <strong style="font-family:'Courier New',monospace;">{{ $bon->order->order_number }}</strong> vient d'être effectué. Vous trouverez le bon d'enlèvement signé en pièce jointe au format PDF.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:14px 18px;font-size:13px;">
            <div><strong>Numéro de bon :</strong> <span style="font-family:'Courier New',monospace;">{{ $bon->bon_number }}</span></div>
            <div><strong>Date :</strong> {{ $bon->picked_up_at?->format('d-m-Y H:i') }}</div>
            @if ($bon->weight_kg) <div><strong>Poids :</strong> {{ $bon->weight_kg }} kg</div> @endif
            @if ($bon->seals->count())
                <div><strong>Nombre de scellés :</strong> {{ $bon->seals->count() }}</div>
            @endif
            <div><strong>Chauffeur :</strong> {{ $bon->driver_name_snapshot ?? '—' }} (permis ****{{ $bon->driver_license_last4 ?? '—' }})</div>
        </td>
    </tr>
</table>

<p style="font-size:13px;color:#555;">Les numéros de scellés et le bon signé constituent votre preuve que le matériel a été enlevé scellé. Conservez cet e-mail et le PDF dans vos dossiers — avec le <strong>Certificat de Destruction</strong> qui suit, ils forment la piste d'audit complète.</p>

<p>Vous recevrez le certificat sous 24 heures, une fois le matériel détruit.</p>

<p>Cordialement,<br>L'équipe DeSnipperaar</p>
@endcomponent
