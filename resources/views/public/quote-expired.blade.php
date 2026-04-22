@extends('public._layout')
@section('title', 'Offerte verlopen')

@section('content')
    <div class="banner bad">
        Deze offerte is verlopen op {{ $order->quote_valid_until->format('d-m-Y') }}.
    </div>
    <h1>Offerte niet meer geldig.</h1>
    <p>De geldigheid van offerte <span class="num">{{ $order->order_number }}</span> is verstreken.</p>
    <p>Neem contact op via <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a> of
    <a href="mailto:{{ $order->senderUser()?->email ?? 'sales@desnipperaar.nl' }}" style="color:#0A0A0A;">{{ $order->senderUser()?->email ?? 'sales@desnipperaar.nl' }}</a>
    voor een nieuwe offerte.</p>
@endsection
