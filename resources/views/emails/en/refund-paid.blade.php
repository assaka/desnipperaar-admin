@component('emails._layout', ['title' => 'Refund sent'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Your money is on its way back.</h1>

<p>Dear {{ explode(' ', $invoice->customer_name)[0] }},</p>

<p>We have refunded the amount of credit note <strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $invoice->invoice_number }}</strong>. Depending on your bank it will be in your account within a few working days.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:14px 18px;">
            <div><strong>Amount:</strong> <span style="font-family:'Courier New',monospace;font-size:16pt;font-weight:900;">€ {{ number_format(abs((float) $invoice->amount_incl_btw), 2, ',', '.') }}</span> incl. VAT</div>
            @if ($invoice->paid_at)
                <div><strong>Refunded on:</strong> {{ $invoice->paid_at->format('d-m-Y') }}</div>
            @endif
            <div><strong>Reference:</strong> {{ $invoice->invoice_number }}</div>
            @if ($invoice->creditsInvoice)
                <div><strong>Belongs to invoice:</strong> {{ $invoice->creditsInvoice->invoice_number }}</div>
            @endif
            @if ($invoice->order)
                <div><strong>Order:</strong> {{ $invoice->order->order_number }}</div>
            @endif
            @if ($invoice->credit_reason)
                <div><strong>Reason:</strong> {{ $invoice->credit_reason }}</div>
            @endif
        </td>
    </tr>
</table>

<p>Keep this email as proof of the refund. You received the credit note itself earlier.</p>

<p>Questions? Call <a href="tel:+31610229965" style="color:#0A0A0A;">+31 6 10229965</a> or reply to this email.</p>

<p>Kind regards,<br>Team DeSnipperaar</p>
@endcomponent
