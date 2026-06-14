@component('emails.es._layout', ['title' => 'Factura '.$invoice->invoice_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Su factura está lista.</h1>

<p>Hola {{ explode(' ', $invoice->customer_name)[0] }},</p>

<p>Aquí tiene la factura <strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $invoice->invoice_number }}</strong> del pedido {{ $invoice->order->order_number }}.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:14px 18px;">
            <div><strong>Importe:</strong> <span style="font-family:'Courier New',monospace;font-size:16pt;font-weight:900;">€ {{ number_format($invoice->amount_incl_btw, 2, ',', '.') }}</span> con IVA</div>
            <div><strong>Fecha de vencimiento:</strong> {{ $invoice->due_at->format('d-m-Y') }} ({{ config('desnipperaar.invoice.payment_terms_days') }} días)</div>
            @if (config('desnipperaar.company.iban'))
                <div style="margin-top:6px;"><strong>IBAN:</strong> <span style="font-family:'Courier New',monospace;">{{ config('desnipperaar.company.iban') }}</span></div>
            @endif
            <div><strong>Referencia:</strong> {{ $invoice->invoice_number }}</div>
        </td>
    </tr>
</table>

<p>La factura con todas las líneas y los datos de pago está en el archivo PDF adjunto.</p>

<p>¿Preguntas? Llame al <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a> o responda a este correo.</p>

<p>Un cordial saludo,<br>El equipo de DeSnipperaar</p>
@endcomponent
