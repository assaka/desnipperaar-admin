@extends('public._layout')
@section('title', 'Offerte '.$order->order_number)

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

    <h2>Voor</h2>
    <div class="row"><span class="k">Naam</span><span class="v" style="font-family:inherit;">{{ $order->customer_name }}</span></div>
    <div class="row"><span class="k">E-mail</span><span class="v" style="font-family:inherit;">{{ $order->customer_email }}</span></div>

    <h2>Scope en prijs</h2>
    <div class="quote-body">{{ $order->quote_body }}</div>

    <div class="meta">
        <div class="row"><span class="k">Bedrag excl. btw</span><span class="v">€ {{ number_format($order->quoted_amount_excl_btw, 2, ',', '.') }}</span></div>
        <div class="row"><span class="k">BTW 21%</span><span class="v">€ {{ number_format($order->quoted_amount_excl_btw * 0.21, 2, ',', '.') }}</span></div>
        <div class="total">€ {{ number_format($order->quoted_amount_excl_btw * 1.21, 2, ',', '.') }}<span class="small">incl. btw</span></div>
    </div>

    @if ($order->quote_valid_until)
        <p class="small">Deze offerte is geldig tot <strong>{{ $order->quote_valid_until->format('d-m-Y') }}</strong>.</p>
    @endif

    @if (!$order->quote_accepted_at && !$order->isQuoteExpired())
        <form method="POST" action="{{ route('quote.accept', $order->quote_token) }}" style="margin-top:24px;">
            @csrf
            <button class="accept-btn">Akkoord — plaats opdracht</button>
            <p class="small" style="margin-top:10px;">
                Door op <strong>Akkoord</strong> te klikken gaat u akkoord met het bedrag
                van <strong>€ {{ number_format($order->quoted_amount_excl_btw * 1.21, 2, ',', '.') }}</strong> incl. btw
                en de <a href="https://desnipperaar.nl/voorwaarden" target="_blank" style="color:#0A0A0A;">algemene voorwaarden</a>.
                Uw IP-adres en tijdstip worden vastgelegd als bewijs.
            </p>
        </form>
    @endif
@endsection
