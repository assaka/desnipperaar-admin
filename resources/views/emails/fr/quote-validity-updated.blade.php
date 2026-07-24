@php $ref = $order->quote_reference ?? $order->order_number; @endphp
@component('emails._layout', ['title' => 'Devis '.$ref])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">La validité de votre devis a été mise à jour.</h1>

<p>Bonjour {{ explode(' ', $order->customer_name)[0] }},</p>

<p>La validité de notre devis <strong style="font-family:monospace;">{{ $ref }}</strong> a été prolongée. Vous pouvez encore l'accepter jusqu'à la nouvelle date ci-dessous.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    @if ($order->quoted_amount_excl_btw)
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Montant hors TVA</td>
        <td style="padding:8px 0;text-align:right;font-weight:900;font-size:18px;font-family:monospace;">
            € {{ number_format($order->quoted_amount_excl_btw, 2, ',', '.') }}
        </td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Montant TVA 21% comprise</td>
        <td style="padding:8px 0;text-align:right;font-family:monospace;border-top:1px solid #EEE;">
            € {{ number_format($order->quoted_amount_excl_btw * 1.21, 2, ',', '.') }}
        </td></tr>
    @endif
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Nouvelle validité jusqu'au</td>
        <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">
            {{ $order->quote_valid_until->format('d-m-Y') }}
        </td></tr>
</table>

<p style="margin:24px 0;text-align:center;">
    <a href="{{ $acceptUrl }}"
       style="display:inline-block;background:#0A0A0A;color:#F5C518;padding:14px 28px;font-weight:900;font-size:16px;text-decoration:none;text-transform:uppercase;letter-spacing:0.05em;">
        Voir le devis →
    </a>
</p>

<p style="font-size:12px;color:#555;">Ce lien est personnel et unique pour votre devis. Sur la page suivante vous voyez tous les détails. Vous renseignez votre adresse et cliquez sur <strong>Passer la commande</strong>. Ce n'est qu'alors que vous concluez un accord pour le montant indiqué ci-dessus. Si vous ne cliquez pas, vous n'êtes engagé à rien.</p>

<p style="font-size:12px;color:#555;">Une question ou une modification ? Répondez simplement à cet e-mail. <strong>Ne changez pas l'objet</strong> pour que votre message soit ajouté automatiquement à votre devis.</p>

<p>Cordialement,<br>L'équipe DeSnipperaar</p>
@endcomponent
