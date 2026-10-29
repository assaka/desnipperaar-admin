@component('emails.fr._layout', ['title' => 'Certificat '.$certificate->certificate_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Votre matériel confidentiel a été détruit.</h1>

<p>Bonjour {{ explode(' ', $certificate->order->customer_name)[0] }},</p>

<p>Vous trouverez votre certificat de destruction pour la commande <strong style="font-family:monospace;">{{ $certificate->order->order_number }}</strong>.</p>

@if (! empty($corrected))
<p style="background:#FFF4D6;border-left:3px solid #F5C518;padding:12px 14px;font-size:13px;"><strong>Version corrigée.</strong>
@if ($certificate->isMobileQuote() && ! $certificate->isOnsite())
Votre devis comprenait l'option de destruction mobile sur site, si bien que la version précédente indiquait un lieu de destruction erroné. Vos documents ont été détruits dans notre dépôt d'Amsterdam-Noord. C'est désormais ce lieu qui figure sur le certificat. Cet exemplaire remplace le certificat reçu précédemment.
@else
Ce certificat remplace l'exemplaire que vous avez reçu précédemment. Veuillez désormais utiliser cette version.
@endif
</p>
@endif

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Numéro de certificat</td>
        <td style="padding:8px 0 8px 12px;text-align:right;white-space:nowrap;font-family:monospace;font-weight:700;">{{ $certificate->certificate_number }}</td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Date de destruction</td>
        <td style="padding:8px 0 8px 12px;text-align:right;white-space:nowrap;font-weight:700;border-top:1px solid #EEE;">{{ $certificate->destroyed_at?->format('d-m-Y') }}</td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Méthode</td>
        <td style="padding:8px 0 8px 12px;text-align:right;white-space:nowrap;font-weight:700;border-top:1px solid #EEE;">{{ $certificate->destruction_method }}</td></tr>
    @if ($certificate->weight_kg_final)
        <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Poids final</td>
            <td style="padding:8px 0 8px 12px;text-align:right;white-space:nowrap;font-weight:700;border-top:1px solid #EEE;">{{ $certificate->weight_kg_final }} kg</td></tr>
    @endif
</table>

<p style="background:#F7F7F4;border-left:3px solid #F5C518;padding:12px 14px;font-size:13px;">Votre certificat de destruction est joint à cet e-mail au format <strong>PDF</strong>. Conservez-le dans vos dossiers.</p>

<p style="font-size:12px;color:#555;">Ce certificat sert de preuve de destruction pour le RGPD, la lutte anti-blanchiment (Wwft),
ainsi que pour la supervision de l'AFM et de la DNB. Conservez cet e-mail ou le PDF dans vos dossiers.</p>

<p>Cordialement,<br>DeSnipperaar</p>
@endcomponent
