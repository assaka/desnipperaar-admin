@extends('public._layout')
@section('title', __('quote.cancel_title'))

@php
    $contactMail = $order->senderUser()?->email ?? 'sales@desnipperaar.nl';
    $phoneLink   = '<a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a>';
    $mailLink    = '<a href="mailto:'.e($contactMail).'" style="color:#0A0A0A;">'.e($contactMail).'</a>';
@endphp

@section('content')
    <div class="banner bad">
        {{ $order->canceled_at
            ? __('quote.cancel_banner_date', ['date' => $order->canceled_at->format('d-m-Y')])
            : __('quote.cancel_banner') }}
    </div>
    <h1>{{ __('quote.cancel_h1') }}</h1>
    <p>{!! __('quote.cancel_p', ['number' => '<span class="num">'.e($order->order_number).'</span>']) !!}</p>
    @if ($order->cancel_reason)
        <p>{{ __('quote.cancel_reason', ['reason' => $order->cancel_reason]) }}</p>
    @endif
    <p>{!! __('quote.cancel_new', ['phone' => $phoneLink, 'email' => $mailLink]) !!}</p>
@endsection
