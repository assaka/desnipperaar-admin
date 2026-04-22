<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>@yield('title', 'DeSnipperaar')</title>
<style>
    :root { --ink:#0A0A0A; --geel:#F5C518; --rule:#E5E5E5; }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, Helvetica, sans-serif; background: #EEECE4; color: var(--ink); line-height: 1.6; }
    .brand-bar { background: var(--geel); padding: 18px 28px; font-family: 'Arial Black', Arial, sans-serif; font-weight: 900; font-size: 22pt; letter-spacing: 0.04em; }
    .wrap { max-width: 720px; margin: 24px auto; padding: 0 16px; }
    .card { background: #FFF; border: 1px solid #DDD; padding: 32px 28px; }
    h1 { font-size: 22pt; font-weight: 900; margin-bottom: 10px; line-height: 1.15; }
    h2 { font-size: 13pt; font-weight: 900; text-transform: uppercase; letter-spacing: 0.02em; margin: 24px 0 10px; }
    p { margin-bottom: 12px; }
    .num { font-family: 'Courier New', monospace; background: var(--geel); padding: 3px 8px; display: inline-block; }
    .meta { border-top: 1px solid var(--rule); border-bottom: 1px solid var(--rule); padding: 16px 0; margin: 20px 0; }
    .row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 14px; }
    .row .k { color: #555; }
    .row .v { font-weight: 700; font-family: monospace; }
    .quote-body { background: #F7F7F4; padding: 14px; border-left: 3px solid var(--geel); white-space: pre-line; font-size: 14px; margin: 14px 0; }
    .total { font-size: 22pt; font-weight: 900; text-align: right; margin-top: 12px; }
    .total .small { font-size: 12pt; color: #555; font-weight: normal; display: block; }
    .accept-btn { display:inline-block; background:#0A0A0A; color:#F5C518; padding:14px 28px; font-weight:900; font-size:16px; text-transform:uppercase; border:0; cursor:pointer; letter-spacing:0.05em; width: 100%; text-align: center; }
    .accept-btn:hover { background: var(--geel); color: var(--ink); }
    .small { font-size: 12px; color: #555; }
    .trust { text-align: center; padding: 16px; font-family: 'Courier New', monospace; font-size: 9pt; letter-spacing: 0.1em; color: #888; }
    .banner { padding: 12px 14px; margin-bottom: 16px; font-size: 14px; }
    .banner.ok { background: #E8F5E9; color: #1B5E20; border-left: 3px solid #2E7D32; }
    .banner.bad { background: #FDECEC; color: #8B1A1A; border-left: 3px solid #D32F2F; }
</style>
</head>
<body>
<header class="brand-bar">DESNIPPERAAR</header>
<div class="wrap">
    <div class="card">
        @yield('content')
    </div>
    <p class="trust">AVG &middot; DIN 66399 &middot; VOG &middot; Verzekerd &middot; €&nbsp;2,5 mln dekking</p>
</div>
</body>
</html>
