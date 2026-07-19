@extends('public._layout')
@section('title', ($order->isAbonnement() ? 'Abonnement bevestigd' : 'Offerte geaccepteerd').' — '.$order->order_number)

@section('content')
    @if ($order->isAbonnement())
        <h1>Bedankt, uw abonnement loopt.</h1>
        <p>Uw abonnement <span class="num">{{ $order->order_number }}</span> is bevestigd.</p>
        <p>Een bevestiging is per e-mail verstuurd naar <strong>{{ $order->customer_email }}</strong>.</p>

        <div class="meta">
            <div class="row"><span class="k">Frequentie</span>
                <span class="v">{{ $order->subFreqLabel() }}</span></div>
            <div class="row"><span class="k">Looptijd</span>
                <span class="v">{{ $order->subTermLabel() }}</span></div>
            @if ($order->sub_price_excl_btw)
            <div class="row"><span class="k">Prijs incl. btw</span>
                <span class="v">€ {{ number_format($order->sub_price_excl_btw * 1.21, 2, ',', '.') }}
                    {{ $order->sub_term === 'jaar' ? 'per jaar' : 'per 4 weken' }}</span></div>
            @endif
            <div class="row"><span class="k">Bevestigd op</span>
                <span class="v">{{ $order->quote_accepted_at->format('d-m-Y H:i') }}</span></div>
        </div>

        <p class="small">Binnen één werkdag nemen wij contact met u op om de container te plaatsen en het eerste ophaalmoment af te spreken.</p>
    @else
        <h1>Bedankt, uw opdracht is geplaatst.</h1>
        <p>Uw offerte voor <span class="num">{{ $order->order_number }}</span> is geaccepteerd.</p>
        <p>Een orderbevestiging is per e-mail verstuurd naar <strong>{{ $order->customer_email }}</strong>.</p>

        <div class="meta">
            @if ($order->quoted_amount_excl_btw)
            <div class="row"><span class="k">Bedrag incl. btw</span>
                <span class="v">€ {{ number_format($order->quoted_amount_excl_btw * 1.21, 2, ',', '.') }}</span></div>
            @endif
            <div class="row"><span class="k">Geaccepteerd op</span>
                <span class="v">{{ $order->quote_accepted_at->format('d-m-Y H:i') }}</span></div>
        </div>

        <p class="small">Binnen één werkdag nemen wij contact met u op om de uitvoering in te plannen.</p>
    @endif
@endsection
