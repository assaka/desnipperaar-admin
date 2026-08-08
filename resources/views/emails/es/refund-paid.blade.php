@component('emails._layout', ['title' => 'Reembolso enviado'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Su dinero está de vuelta.</h1>

<p>Estimado/a {{ explode(' ', $invoice->customer_name)[0] }},</p>

<p>Le hemos devuelto el importe de la factura de abono <strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $invoice->invoice_number }}</strong>. Según su banco, estará en su cuenta en unos días laborables.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:14px 18px;">
            <div><strong>Importe:</strong> <span style="font-family:'Courier New',monospace;font-size:16pt;font-weight:900;">€ {{ number_format(abs((float) $invoice->amount_incl_btw), 2, ',', '.') }}</span> IVA incluido</div>
            @if ($invoice->paid_at)
                <div><strong>Devuelto el:</strong> {{ $invoice->paid_at->format('d-m-Y') }}</div>
            @endif
            <div><strong>Referencia:</strong> {{ $invoice->invoice_number }}</div>
            @if ($invoice->creditsInvoice)
                <div><strong>Corresponde a la factura:</strong> {{ $invoice->creditsInvoice->invoice_number }}</div>
            @endif
            @if ($invoice->order)
                <div><strong>Pedido:</strong> {{ $invoice->order->order_number }}</div>
            @endif
            @if ($invoice->credit_reason)
                <div><strong>Motivo:</strong> {{ $invoice->credit_reason }}</div>
            @endif
        </td>
    </tr>
</table>

<p>Guarde este correo como comprobante del reembolso. La factura de abono ya la recibió anteriormente.</p>

<p>¿Preguntas? Llame al <a href="tel:+31610229965" style="color:#0A0A0A;">+31 6 10229965</a> o responda a este correo.</p>

<p>Un saludo,<br>Equipo DeSnipperaar</p>
@endcomponent
