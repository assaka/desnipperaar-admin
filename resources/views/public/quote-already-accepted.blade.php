@extends('public._layout')
@section('title', 'Offerte al geaccepteerd')

@section('content')
    <div class="banner ok">
        Deze offerte is al geaccepteerd op {{ $order->quote_accepted_at->format('d-m-Y H:i') }}.
    </div>
    <h1>Uw opdracht is al geplaatst.</h1>
    <p>Order <span class="num">{{ $order->order_number }}</span> is in behandeling.
    U heeft de orderbevestiging per e-mail ontvangen op <strong>{{ $order->customer_email }}</strong>.</p>
    <p class="small">Geen mail ontvangen? Check spam, of neem contact op via 06-10229965.</p>
@endsection
