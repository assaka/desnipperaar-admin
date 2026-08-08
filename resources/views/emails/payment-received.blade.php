@component('emails._layout', ['title' => 'Betaling ontvangen'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Uw betaling is binnen.</h1>

<p>Beste {{ explode(' ', $invoice->customer_name)[0] }},</p>

<p>Wij hebben uw betaling van factuur <strong style="font-family:'Courier New',monospace;background:#F5C518;padding:2px 6px;">{{ $invoice->invoice_number }}</strong> ontvangen. Daarmee is deze order afgerond en hoeft u niets meer te doen.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:20px 0;background:#F7F7F4;border-left:4px solid #F5C518;">
    <tr>
        <td style="padding:14px 18px;">
            <div><strong>Bedrag:</strong> <span style="font-family:'Courier New',monospace;font-size:16pt;font-weight:900;">€ {{ number_format($invoice->amount_incl_btw, 2, ',', '.') }}</span> incl. btw</div>
            @if ($invoice->paid_at)
                <div><strong>Ontvangen op:</strong> {{ $invoice->paid_at->format('d-m-Y') }}</div>
            @endif
            <div><strong>Kenmerk:</strong> {{ $invoice->invoice_number }}</div>
            @if ($invoice->order)
                <div><strong>Order:</strong> {{ $invoice->order->order_number }}</div>
            @endif
        </td>
    </tr>
</table>

<p>Bewaar deze mail als bewijs van betaling. De factuur zelf heeft u eerder ontvangen.</p>

<p>Vragen? Bel <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a> of reply op deze mail.</p>

<p>Met vriendelijke groet,<br>Team DeSnipperaar</p>
@endcomponent
