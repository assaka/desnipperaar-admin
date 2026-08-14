@extends('public._layout')
@section('title', 'Offerte geannuleerd')

@section('content')
    <div class="banner bad">
        Deze offerte is geannuleerd{{ $order->canceled_at ? ' op '.$order->canceled_at->format('d-m-Y') : '' }}.
    </div>
    <h1>Offerte niet meer geldig.</h1>
    <p>Offerte <span class="num">{{ $order->order_number }}</span> is ingetrokken en kan niet meer worden geaccepteerd.</p>
    @if ($order->cancel_reason)
        <p>De reden die wij erbij noteerden is "{{ $order->cancel_reason }}".</p>
    @endif
    <p>Gaat het toch door? Neem contact op via <a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a> of
    <a href="mailto:{{ $order->senderUser()?->email ?? 'sales@desnipperaar.nl' }}" style="color:#0A0A0A;">{{ $order->senderUser()?->email ?? 'sales@desnipperaar.nl' }}</a>
    voor een nieuwe offerte.</p>
@endsection
