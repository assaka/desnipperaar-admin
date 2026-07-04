@extends('public._layout')
@section('title', 'Offerte '.$order->order_number)

@php
    $isOffer  = !is_null($order->quoted_amount_excl_btw);
    $active   = $isOffer && !$order->quote_accepted_at && !$order->isQuoteExpired();
    $allLines = $order->quote_lines ?? [];
    $hasOptional = collect($allLines)->contains(fn ($l) => !empty($l['optional']));
    $baseExcl = (float) ($order->quoted_amount_excl_btw ?? 0);
    $eur  = fn ($n) => '€ '.number_format((float) $n, 2, ',', '.');
    $qtyf = fn ($q) => 0.0 == fmod((float) $q, 1) ? number_format($q, 0, ',', '.') : number_format($q, 2, ',', '.');
@endphp

@section('content')
    @if ($order->quote_accepted_at)
        <div class="banner ok">
            Deze offerte is al geaccepteerd op {{ $order->quote_accepted_at->format('d-m-Y H:i') }}.
            Een orderbevestiging is onderweg.
        </div>
    @elseif ($order->isQuoteExpired())
        <div class="banner bad">
            Deze offerte is verlopen op {{ $order->quote_valid_until->format('d-m-Y') }}.
            Neem contact op voor een nieuwe offerte.
        </div>
    @endif

    <div style="font-family:'Courier New',monospace;font-size:10pt;letter-spacing:0.12em;color:#555;text-transform:uppercase;margin-bottom:6px;">Offerte</div>
    <h1>Uw offerte op maat</h1>
    <div class="num">{{ $order->order_number }}</div>

    <h2>Scope en prijs</h2>

    @if (collect($allLines)->contains(fn ($l) => empty($l['optional'])))
    <table class="lines">
        <thead>
            <tr><th>Omschrijving</th><th class="r">Aantal</th><th class="r">Prijs</th><th class="r">Subtotaal</th></tr>
        </thead>
        <tbody>
            @foreach ($allLines as $line)
                @if (empty($line['optional']))
                <tr>
                    <td>{{ $line['label'] }}</td>
                    <td class="r">{{ $qtyf($line['qty']) }}</td>
                    <td class="r">{{ $eur($line['unit']) }}</td>
                    <td class="r">{{ $eur($line['subtotal']) }}</td>
                </tr>
                @endif
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Totals block, reused for the active-offer form and the read-only states. --}}
    @php
        $totalsBlock = function () use ($isOffer, $eur, $baseExcl) {
            if (! $isOffer) return '';
            return '<div class="meta">'
                .'<div class="row"><span class="k">Bedrag excl. btw</span><span class="v" id="amt-excl">'.$eur($baseExcl).'</span></div>'
                .'<div class="row"><span class="k">BTW 21%</span><span class="v" id="amt-btw">'.$eur($baseExcl * 0.21).'</span></div>'
                .'<div class="total"><span id="amt-incl">'.$eur($baseExcl * 1.21).'</span><span class="small">incl. btw</span></div>'
                .'</div>';
        };
    @endphp

    @if ($active)
        @php $inclBtw = $eur($baseExcl * 1.21); @endphp

        @if ($errors->any())
            <div class="banner bad">
                @foreach ($errors->all() as $e)
                    {{ $e }}<br>
                @endforeach
            </div>
        @endif

        <form id="accept-form" method="POST" action="{{ rtrim(config('desnipperaar.public_url'), '/').'/offerte/'.$order->quote_token.'/accept' }}" style="margin-top:16px;">
            @csrf

            @if ($hasOptional)
            <h2>Extra opties</h2>
            <p class="small">Vink aan wat u wilt toevoegen. Het totaal past zich direct aan.</p>
            <table class="lines">
                <tbody>
                    @foreach ($allLines as $i => $line)
                        @if (!empty($line['optional']))
                        <tr>
                            <td style="width:26px;"><input type="checkbox" class="opt-line" id="opt-{{ $i }}" name="optional_lines[]" value="{{ $i }}" data-subtotal="{{ number_format($line['subtotal'], 2, '.', '') }}"></td>
                            <td><label for="opt-{{ $i }}">{{ $line['label'] }}</label></td>
                            <td class="r">{{ $qtyf($line['qty']) }}</td>
                            <td class="r">{{ $eur($line['unit']) }}</td>
                            <td class="r">+ {{ $eur($line['subtotal']) }}</td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
            @endif

            @if ($order->quote_body)
            <div class="quote-body">{{ $order->quote_body }}</div>
            @endif

            {!! $totalsBlock() !!}

            @if ($order->quote_valid_until)
                <p class="small">Deze offerte is geldig tot <strong>{{ $order->quote_valid_until->format('d-m-Y') }}</strong>.</p>
            @endif

            <h2>Uw gegevens</h2>
            <p class="small">Vul het adres in waar wij de opdracht uitvoeren. Daarna plaatst u de opdracht.</p>

            <div class="field">
                <label for="naam">Naam</label>
                <input type="text" id="naam" name="naam" required
                       value="{{ old('naam', $order->customer_name) }}" autocomplete="name">
            </div>
            <div class="field-row">
                <div class="field" style="flex:1;">
                    <label for="email">E-mailadres</label>
                    <input type="email" id="email" name="email" required
                           value="{{ old('email', $order->customer_email) }}" autocomplete="email">
                </div>
                <div class="field" style="flex:1;">
                    <label for="bedrijf">Bedrijf <span style="font-weight:400;color:#999;">(optioneel)</span></label>
                    <input type="text" id="bedrijf" name="bedrijf"
                           value="{{ old('bedrijf', optional($order->customer)->company) }}" autocomplete="organization">
                </div>
            </div>
            <div class="field">
                <label for="telefoon">Telefoon</label>
                <input type="tel" id="telefoon" name="telefoon" required
                       value="{{ old('telefoon', $order->customer_phone) }}" autocomplete="tel">
            </div>
            <div class="field-row">
                <div class="field" style="flex:3;">
                    <label for="straat">Straatnaam</label>
                    <input type="text" id="straat" name="straat" required
                           value="{{ old('straat') }}" autocomplete="street-address">
                </div>
                <div class="field" style="flex:1;">
                    <label for="huisnummer">Huisnummer</label>
                    <input type="text" id="huisnummer" name="huisnummer" required
                           value="{{ old('huisnummer') }}" autocomplete="address-line2">
                </div>
            </div>
            <div class="field-row">
                <div class="field" style="flex:1;">
                    <label for="postcode">Postcode</label>
                    <input type="text" id="postcode" name="postcode" required
                           value="{{ old('postcode', $order->customer_postcode) }}"
                           pattern="\d{4}\s?[A-Za-z]{2}"
                           style="font-family:monospace;text-transform:uppercase;" autocomplete="postal-code">
                </div>
                <div class="field" style="flex:1;">
                    <label for="stad">Stad</label>
                    <input type="text" id="stad" name="stad" required
                           value="{{ old('stad', $order->customer_city) }}" autocomplete="address-level2">
                </div>
            </div>

            <button class="accept-btn" id="accept-btn" style="margin-top:20px;">Plaats opdracht</button>
            <p class="small" style="margin-top:10px;">
                Door op <strong>Plaats opdracht</strong> te klikken gaat u akkoord met het bedrag
                van <strong><span id="legal-incl">{{ $inclBtw }}</span></strong> incl. btw
                en de <a href="https://desnipperaar.nl/voorwaarden" target="_blank" style="color:#0A0A0A;">algemene voorwaarden</a>.
                Uw IP-adres en tijdstip worden vastgelegd als bewijs.
            </p>
        </form>

        <div id="accept-modal" class="modal-overlay" aria-hidden="true">
            <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="accept-modal-title">
                <h2 id="accept-modal-title" style="margin-top:0;">Opdracht plaatsen?</h2>
                <p>U plaatst nu een opdracht op basis van offerte <strong style="font-family:monospace;">{{ $order->order_number }}</strong>.
                   Dit is een bindende opdracht voor <strong><span id="modal-incl">{{ $inclBtw }}</span></strong> incl. btw.</p>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="accept-cancel">Annuleer</button>
                    <button type="button" class="accept-btn" id="accept-confirm" style="width:auto;">Ja, plaats opdracht</button>
                </div>
            </div>
        </div>

        <style>
            .modal-overlay { display:none; position:fixed; inset:0; background:rgba(10,10,10,0.55); z-index:50; }
            .modal-overlay.open { display:flex; align-items:center; justify-content:center; padding:16px; }
            .modal-box { background:#FFF; border:1px solid #DDD; max-width:460px; width:100%; padding:26px 24px; }
            .modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:20px; flex-wrap:wrap; }
            .btn-secondary { background:#FFF; color:var(--ink); border:2px solid var(--ink); padding:14px 22px; font-weight:900; font-size:16px; text-transform:uppercase; cursor:pointer; letter-spacing:0.05em; }
            .btn-secondary:hover { background:var(--ink); color:#FFF; }
            table.lines .opt-line { width:18px; height:18px; }
        </style>

        <script>
            (function () {
                var form    = document.getElementById('accept-form');
                var overlay = document.getElementById('accept-modal');
                var confirmed = false;
                var base = {{ number_format($baseExcl, 2, '.', '') }};

                function euro(n) {
                    return '€ ' + n.toLocaleString('nl-NL', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
                function setText(id, txt) { var el = document.getElementById(id); if (el) el.textContent = txt; }

                function recalc() {
                    var total = base;
                    document.querySelectorAll('.opt-line:checked').forEach(function (cb) {
                        total += parseFloat(cb.dataset.subtotal) || 0;
                    });
                    var incl = total * 1.21;
                    setText('amt-excl', euro(total));
                    setText('amt-btw',  euro(total * 0.21));
                    setText('amt-incl', euro(incl));
                    setText('legal-incl', euro(incl));
                    setText('modal-incl', euro(incl));
                }

                document.querySelectorAll('.opt-line').forEach(function (cb) {
                    cb.addEventListener('change', recalc);
                });
                recalc();

                // Progressive enhancement: without JS the button submits directly.
                form.addEventListener('submit', function (e) {
                    if (confirmed) return;
                    e.preventDefault();
                    overlay.classList.add('open');
                    overlay.setAttribute('aria-hidden', 'false');
                });

                function close() {
                    overlay.classList.remove('open');
                    overlay.setAttribute('aria-hidden', 'true');
                }

                document.getElementById('accept-cancel').addEventListener('click', close);
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) close();
                });
                document.getElementById('accept-confirm').addEventListener('click', function () {
                    confirmed = true;
                    form.submit();
                });
            })();
        </script>
    @else
        @if ($order->quote_body)
        <div class="quote-body">{{ $order->quote_body }}</div>
        @endif

        {!! $totalsBlock() !!}

        @if ($order->quote_valid_until)
            <p class="small">Deze offerte is geldig tot <strong>{{ $order->quote_valid_until->format('d-m-Y') }}</strong>.</p>
        @endif
    @endif
@endsection
