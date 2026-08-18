@extends('public._layout')
@section('title', __('quote.exp_title'))

@php
    $contactMail = $order->senderUser()?->email ?? 'sales@desnipperaar.nl';
    $phoneLink   = '<a href="tel:+31610229965" style="color:#0A0A0A;">06-10229965</a>';
    $mailLink    = '<a href="mailto:'.e($contactMail).'" style="color:#0A0A0A;">'.e($contactMail).'</a>';
@endphp

@section('content')
    <div class="banner bad">
        {{ __('quote.exp_banner', ['date' => $order->quote_valid_until->format('d-m-Y')]) }}
    </div>
    <h1>{{ __('quote.exp_h1') }}</h1>
    <p>{!! __('quote.exp_p', ['number' => '<span class="num">'.e($order->order_number).'</span>']) !!}</p>
    <p>{!! __('quote.exp_new', ['phone' => $phoneLink, 'email' => $mailLink]) !!}</p>
@endsection
