@extends('public._layout')
@section('title', __('quote.done_title'))

@section('content')
    <div class="banner ok">
        {{ __('quote.done_banner', ['date' => $order->quote_accepted_at->format('d-m-Y H:i')]) }}
    </div>
    <h1>{{ __('quote.done_h1') }}</h1>
    <p>{!! __('quote.done_p', [
        'number' => '<span class="num">'.e($order->order_number).'</span>',
        'email'  => '<strong>'.e($order->customer_email).'</strong>',
    ]) !!}</p>
    <p class="small">{{ __('quote.done_small', ['phone' => '06-10229965']) }}</p>
@endsection
