@extends('public._layout')
@section('title', 'Offerte geaccepteerd — '.$order->order_number)

@section('content')
    <h1>Bedankt — uw opdracht is geplaatst.</h1>
    <p>Uw offerte voor <span class="num">{{ $order->order_number }}</span> is geaccepteerd.</p>
    <p>Een orderbevestiging is per e-mail verstuurd naar <strong>{{ $order->customer_email }}</strong>.</p>

    <div class="meta">
        <div class="row"><span class="k">Bedrag incl. btw</span>
            <span class="v">€ {{ number_format($order->quoted_amount_excl_btw * 1.21, 2, ',', '.') }}</span></div>
        <div class="row"><span class="k">Geaccepteerd op</span>
            <span class="v">{{ $order->quote_accepted_at->format('d-m-Y H:i') }}</span></div>
    </div>

    <p class="small">Binnen één werkdag nemen wij contact met u op om de uitvoering in te plannen.</p>
@endsection
