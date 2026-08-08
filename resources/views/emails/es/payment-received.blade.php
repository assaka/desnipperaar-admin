@component('emails._layout', ['title' => 'Pago recibido'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Su pago ha llegado.</h1>

<p>Estimado/a {{ explode(' ', $invoice->customer_name)[0] }},</p>

<p>Hemos recibido su pago de la factura <strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $invoice->invoice_number }}</strong>. Con esto el pedido queda cerrado y no tiene que hacer nada más.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:14px 18px;">
            <div><strong>Importe:</strong> <span style="font-family:'Courier New',monospace;font-size:16pt;font-weight:900;">€ {{ number_format($invoice->amount_incl_btw, 2, ',', '.') }}</span> IVA incluido</div>
            @if ($invoice->paid_at)
                <div><strong>Recibido el:</strong> {{ $invoice->paid_at->format('d-m-Y') }}</div>
            @endif
            <div><strong>Referencia:</strong> {{ $invoice->invoice_number }}</div>
            @if ($invoice->order)
                <div><strong>Pedido:</strong> {{ $invoice->order->order_number }}</div>
            @endif
        </td>
    </tr>
</table>

<p>Guarde este correo como comprobante de pago. La factura ya la recibió anteriormente.</p>

<p>¿Preguntas? Llame al <a href="tel:+31610229965" style="color:#0A0A0A;">+31 6 10229965</a> o responda a este correo.</p>

<p>Un saludo,<br>Equipo DeSnipperaar</p>
@endcomponent
