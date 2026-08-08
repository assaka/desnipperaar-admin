@component('emails._layout', ['title' => 'Remboursement envoyé'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Votre argent vous a été renvoyé.</h1>

<p>Bonjour {{ explode(' ', $invoice->customer_name)[0] }},</p>

<p>Nous vous avons remboursé le montant de l'avoir <strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $invoice->invoice_number }}</strong>. Selon votre banque, il sera sur votre compte sous quelques jours ouvrés.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:14px 18px;">
            <div><strong>Montant&nbsp;:</strong> <span style="font-family:'Courier New',monospace;font-size:16pt;font-weight:900;">€ {{ number_format(abs((float) $invoice->amount_incl_btw), 2, ',', '.') }}</span> TVA comprise</div>
            @if ($invoice->paid_at)
                <div><strong>Remboursé le&nbsp;:</strong> {{ $invoice->paid_at->format('d-m-Y') }}</div>
            @endif
            <div><strong>Référence&nbsp;:</strong> {{ $invoice->invoice_number }}</div>
            @if ($invoice->creditsInvoice)
                <div><strong>Concerne la facture&nbsp;:</strong> {{ $invoice->creditsInvoice->invoice_number }}</div>
            @endif
            @if ($invoice->order)
                <div><strong>Commande&nbsp;:</strong> {{ $invoice->order->order_number }}</div>
            @endif
            @if ($invoice->credit_reason)
                <div><strong>Motif&nbsp;:</strong> {{ $invoice->credit_reason }}</div>
            @endif
        </td>
    </tr>
</table>

<p>Conservez cet e-mail comme preuve du remboursement. Vous avez reçu l'avoir lui-même précédemment.</p>

<p>Des questions&nbsp;? Appelez le <a href="tel:+31610229965" style="color:#0A0A0A;">+31 6 10229965</a> ou répondez à cet e-mail.</p>

<p>Cordialement,<br>L'équipe DeSnipperaar</p>
@endcomponent
