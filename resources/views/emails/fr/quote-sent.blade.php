@php $isOffer = !is_null($order->quoted_amount_excl_btw); $ref = $order->quote_reference ?? $order->order_number; @endphp
@component('emails.fr._layout', ['title' => ($isOffer ? 'Devis ' : 'Message ').$ref])
@if ($isOffer)
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Votre devis est prêt.</h1>
@else
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Un message concernant votre demande.</h1>
@endif

<p>Bonjour {{ explode(' ', $order->customer_name)[0] }},</p>

@if ($isOffer)
<p>Voici notre devis pour la demande <strong style="font-family:monospace;">{{ $ref }}</strong>.</p>
@else
<p>Nous avons un message pour vous concernant la demande <strong style="font-family:monospace;">{{ $ref }}</strong>. Vous le trouverez ci-dessous.</p>
@endif

@if ($order->quoted_amount_excl_btw)
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:16px 0;border-top:1px solid #EEE;">
    <tr><td style="padding:8px 0;color:#555;font-size:12px;">Montant hors TVA</td>
        <td style="padding:8px 0;text-align:right;font-weight:900;font-size:18px;font-family:monospace;">
            € {{ number_format($order->quoted_amount_excl_btw, 2, ',', '.') }}
        </td></tr>
    <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Montant TVA 21% comprise</td>
        <td style="padding:8px 0;text-align:right;font-family:monospace;border-top:1px solid #EEE;">
            € {{ number_format($order->quoted_amount_excl_btw * 1.21, 2, ',', '.') }}
        </td></tr>
    @if ($order->quote_valid_until)
        <tr><td style="padding:8px 0;color:#555;font-size:12px;border-top:1px solid #EEE;">Valable jusqu'au</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;border-top:1px solid #EEE;">
                {{ $order->quote_valid_until->format('d-m-Y') }}
            </td></tr>
    @endif
</table>
@endif

<div style="white-space:pre-line;font-size:14px;line-height:1.6;background:#F7F7F4;padding:14px;border-left:3px solid #F5C518;margin:16px 0;">{{ $order->quote_body }}</div>

@if ($order->quoted_amount_excl_btw)
<p style="margin:24px 0;text-align:center;">
    <a href="{{ $acceptUrl }}"
       style="display:inline-block;background:#0A0A0A;color:#F5C518;padding:14px 28px;font-weight:900;font-size:16px;text-decoration:none;text-transform:uppercase;letter-spacing:0.05em;">
        Voir le devis →
    </a>
</p>

<p style="font-size:12px;color:#555;">Ce lien est personnel et propre à votre devis. La page suivante affiche tous les détails.
Vous renseignez votre adresse et cliquez sur <strong>Passer la commande</strong>. Ce n'est qu'alors que vous concluez un accord pour le montant indiqué ci-dessus.
Si vous ne cliquez pas, vous n'êtes engagé à rien.</p>
@else
<p style="font-size:12px;color:#555;">Vous pouvez simplement répondre à cet e-mail, votre message nous parviendra directement.</p>
@endif

<p>Cordialement,<br>L'équipe DeSnipperaar</p>
@endcomponent
