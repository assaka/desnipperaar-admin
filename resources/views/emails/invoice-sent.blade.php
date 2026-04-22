@component('emails._layout', ['title' => 'Factuur '.$invoice->invoice_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Uw factuur is klaar.</h1>

<p>Beste {{ explode(' ', $invoice->customer_name)[0] }},</p>

<p>Hierbij factuur <strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $invoice->invoice_number }}</strong> voor order {{ $invoice->order->order_number }}.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:14px 18px;">
            <div><strong>Bedrag:</strong> <span style="font-family:'Courier New',monospace;font-size:16pt;font-weight:900;">€ {{ number_format($invoice->amount_incl_btw, 2, ',', '.') }}</span> incl. btw</div>
            <div><strong>Vervaldatum:</strong> {{ $invoice->due_at->format('d-m-Y') }} ({{ config('desnipperaar.invoice.payment_terms_days') }} dagen)</div>
            @if (config('desnipperaar.company.iban'))
                <div style="margin-top:6px;"><strong>IBAN:</strong> <span style="font-family:'Courier New',monospace;">{{ config('desnipperaar.company.iban') }}</span></div>
            @endif
            <div><strong>Kenmerk:</strong> {{ $invoice->invoice_number }}</div>
        </td>
    </tr>
</table>

<p>De factuur met alle regels + betaalgegevens zit in de PDF-bijlage.</p>

<p>Vragen? Bel <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a> of reply op deze mail.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
