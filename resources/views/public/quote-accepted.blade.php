@extends('public._layout')
@section('title', ($order->isAbonnement() ? __('quote.sub_title') : __('quote.ok_title')).' — '.$order->order_number)

@section('content')
    @if ($order->isAbonnement())
        <h1>{{ __('quote.sub_h1') }}</h1>
        <p>{!! __('quote.sub_p', ['number' => '<span class="num">'.e($order->order_number).'</span>']) !!}</p>
        <p>{!! __('quote.sub_mail', ['email' => '<strong>'.e($order->customer_email).'</strong>']) !!}</p>

        <div class="meta">
            <div class="row"><span class="k">{{ __('quote.sub_freq') }}</span>
                <span class="v">{{ $order->subFreqLabel() }}</span></div>
            <div class="row"><span class="k">{{ __('quote.sub_term') }}</span>
                <span class="v">{{ $order->subTermLabel() }}</span></div>
            @if ($order->sub_price_excl_btw)
            <div class="row"><span class="k">{{ __('quote.sub_price') }}</span>
                <span class="v">€ {{ number_format($order->sub_price_excl_btw * 1.21, 2, ',', '.') }}
                    {{ $order->sub_term === 'jaar' ? __('quote.sub_per_year') : __('quote.sub_per_4w') }}</span></div>
            @endif
            <div class="row"><span class="k">{{ __('quote.sub_confirmed_at') }}</span>
                <span class="v">{{ $order->quote_accepted_at->format('d-m-Y H:i') }}</span></div>
        </div>

        <p class="small">{{ __('quote.sub_next') }}</p>
    @else
        <h1>{{ __('quote.ok_h1') }}</h1>
        <p>{!! __('quote.ok_p', ['number' => '<span class="num">'.e($order->order_number).'</span>']) !!}</p>
        <p>{!! __('quote.ok_mail', ['email' => '<strong>'.e($order->customer_email).'</strong>']) !!}</p>

        <div class="meta">
            @if ($order->quoted_amount_excl_btw)
            <div class="row"><span class="k">{{ __('quote.ok_amount') }}</span>
                <span class="v">€ {{ number_format($order->quoted_amount_excl_btw * 1.21, 2, ',', '.') }}</span></div>
            @endif
            <div class="row"><span class="k">{{ __('quote.ok_accepted_at') }}</span>
                <span class="v">{{ $order->quote_accepted_at->format('d-m-Y H:i') }}</span></div>
        </div>

        <p class="small">{{ __('quote.ok_next') }}</p>
    @endif
@endsection
