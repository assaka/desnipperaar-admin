@component('emails.en._layout', ['title' => 'DestructionDay'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Today is the DestructionDay.</h1>

<p>One random day each week we give {{ $pct }}% off. Today is that day. Today only, and only for you as a subscriber.</p>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:22px 0;background:#0A0A0A;">
    <tr>
        <td style="padding:22px;text-align:center;">
            <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.14em;text-transform:uppercase;color:#F5C518;margin-bottom:8px;">Your discount code</div>
            <div style="font-family:'Courier New',monospace;font-weight:900;font-size:30px;letter-spacing:0.12em;color:#FFFFFF;">{{ $code }}</div>
            <div style="font-size:13px;color:#BBB;margin-top:6px;">{{ $pct }}% off, valid until midnight tonight</div>
        </td>
    </tr>
</table>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 8px;">
    <tr><td align="center">
        <a href="{{ $orderUrl }}" style="display:inline-block;background:#F5C518;color:#0A0A0A;font-weight:900;font-size:16px;text-decoration:none;padding:16px 32px;">Order now with {{ $pct }}% off</a>
    </td></tr>
</table>

<p style="font-size:13px;color:#555;margin-top:20px;">The code is active automatically today and expires at midnight. See you on the next DestructionDay.</p>

<p>Team DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    You receive this because you signed up for the DestructionDay.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Unsubscribe</a>.
</p>
@endcomponent
