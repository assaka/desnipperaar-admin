<!DOCTYPE html>
<html lang="nl"><head><meta charset="UTF-8"><title>{{ $title ?? 'DeSnipperaar' }}</title></head>
<body style="margin:0;padding:0;background:#EEECE4;font-family:Arial,Helvetica,sans-serif;color:#0A0A0A;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#EEECE4;">
  <tr><td align="center" style="padding:24px 12px;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="640" style="max-width:640px;background:#FFFFFF;border:1px solid #DDD;">
      <tr><td style="background:#F5C518;padding:22px 28px;font-family:'Arial Black',Arial,sans-serif;font-weight:900;font-size:28px;letter-spacing:0.04em;color:#0A0A0A;line-height:1;">DESNIPPERAAR</td></tr>
      <tr><td style="padding:28px;font-size:14px;line-height:1.6;color:#222;">
        {{ $slot }}
      </td></tr>
      <tr><td style="background:#0A0A0A;color:#BBB;padding:18px 28px;font-size:11px;line-height:1.6;">
        DeSnipperaar &middot; Amsterdam &middot;
        <a href="https://desnipperaar.nl" style="color:#F5C518;text-decoration:none;">desnipperaar.nl</a><br>
        AVG &middot; DIN 66399 &middot; VOG &middot; Verzekerd
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
