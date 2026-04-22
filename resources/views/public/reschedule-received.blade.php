@extends('public._layout')
@section('title', 'Wijzigingsverzoek ontvangen — '.$order->order_number)

@section('content')
    <div class="banner ok">✓ Wij hebben uw verzoek ontvangen.</div>

    <h1>Bedankt.</h1>
    <p>Voor <span class="num">{{ $order->order_number }}</span>.</p>

    <div class="meta">
        <div class="row"><span class="k">Voorgesteld</span>
            <span class="v">{{ $order->reschedule_requested_date->locale('nl')->translatedFormat('l d F Y') }} — {{ ucfirst($order->reschedule_requested_window) }}</span></div>
        <div class="row"><span class="k">Ontvangen</span>
            <span class="v">{{ $order->reschedule_requested_at->format('d-m-Y H:i') }}</span></div>
    </div>

    <p>We controleren de beschikbaarheid en bevestigen uw nieuwe ophaalmoment per e-mail.</p>
    <p class="small">Spoed? Bel <a href="tel:+31610229965" style="color:inherit;"><strong>06-10229965</strong></a>.</p>
@endsection
