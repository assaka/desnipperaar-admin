{{--
    Kortingscode op een bestaande order.

    Staat bij de factuur en niet bij de prijsregels, omdat dit het bedrag is dat
    de klant uiteindelijk betaalt: wie hier iets wijzigt, wijzigt een factuur.
--}}
@php
    $couponInvoices = $order->invoices->reject->isCreditNote();
    $paidInvoice    = $couponInvoices->firstWhere('status', \App\Models\Invoice::STATUS_PAID);
    $sentInvoice    = $couponInvoices->firstWhere('status', \App\Models\Invoice::STATUS_SENT);
@endphp

<section class="mb-6">
    <h2 class="font-black mb-2">Kortingscode</h2>

    @error('coupon_code')
        <div class="border-l-4 border-red-700 bg-red-50 pl-3 py-2 mb-2 text-sm text-red-800">{{ $message }}</div>
    @enderror

    @if ($order->hasCoupon())
        <div class="border-l-4 border-green-700 pl-3 py-2 mb-2 flex justify-between items-baseline gap-3">
            <div>
                <span class="font-mono font-bold">{{ $order->coupon_code }}</span>
                @if ($order->coupon_type === 'percentage' && $order->coupon_value > 0)
                    <span class="ml-2 text-sm">
                        {{ \App\Support\Pricing::formatPercentage((float) $order->coupon_value) }}%
                        @if ($order->coupon_base > 0)
                            × € {{ number_format($order->coupon_base, 2, ',', '.') }}
                        @endif
                        =
                    </span>
                @else
                    <span class="ml-2 text-sm"></span>
                @endif
                <span class="text-sm font-bold">− € {{ number_format($order->coupon_discount, 2, ',', '.') }}</span>
                <span class="text-sm">excl. btw</span>
                @if ($order->coupon_applied_at)
                    <div class="text-xs text-gray-500">toegekend {{ $order->coupon_applied_at->format('Y-m-d H:i') }}</div>
                @endif
            </div>
            @if ($order->canWithdrawCoupon())
                <form method="POST" action="{{ route('orders.coupon.destroy', $order) }}"
                      onsubmit="return confirm('Kortingscode {{ $order->coupon_code }} intrekken en de facturen herrekenen?\n\nEr gaat geen mail naar de klant. Gebruik daarvoor Resend.')">
                    @csrf
                    @method('DELETE')
                    <button class="bg-gray-200 text-black px-2 py-0.5 text-xs uppercase font-bold">Intrekken</button>
                </form>
            @else
                {{-- Opgehaald: de klant heeft het bedrag met korting al gezien. --}}
                <span class="text-xs text-gray-500 whitespace-nowrap">order is afgehandeld</span>
            @endif
        </div>
    @elseif ($paidInvoice)
        {{-- Betaald: een korting die er nu nog op komt wordt nergens verrekend. --}}
        <p class="text-sm text-gray-500">
            Factuur {{ $paidInvoice->invoice_number }} is betaald, dus een kortingscode wordt niet meer verrekend.
            Moet er geld terug, maak dan een creditfactuur.
        </p>
    @elseif ($order->isCanceled())
        <p class="text-sm text-gray-500">
            Deze order is geannuleerd, er wordt niets meer gefactureerd. Een kortingscode heeft hier niets meer te doen.
        </p>
    @elseif ($order->isAbonnement())
        <p class="text-sm text-gray-500">
            Een abonnement wordt per periode gefactureerd, daar hoort geen kortingscode per order op.
        </p>
    @else
        <form method="POST" action="{{ route('orders.coupon.store', $order) }}" class="flex flex-wrap items-end gap-2">
            @csrf
            <label class="text-sm">
                <span class="block text-gray-600 mb-1">Code</span>
                <input type="text" name="coupon_code" required maxlength="50"
                       placeholder="WELKOM25"
                       class="border px-2 py-1 text-sm font-mono uppercase w-40">
            </label>
            <button type="submit" class="bg-black text-yellow-400 px-3 py-1 text-sm font-bold">Toekennen</button>
        </form>
        {{-- Toekennen en intrekken raken alleen de order en de facturen. De klant
             hoort er pas van als je zelf Resend gebruikt. --}}
        <p class="text-xs text-gray-500 mt-2">
            Toekennen verstuurt geen mail. De klant hoort het pas als je Resend gebruikt.
        </p>
    @endif

    @if ($paidInvoice)
        <p class="text-xs text-gray-500 mt-2">
            Factuur {{ $paidInvoice->invoice_number }} is betaald en blijft ongemoeid. Een korting daarop loopt via een
            creditfactuur, niet door het origineel te herschrijven.
        </p>
    @elseif ($sentInvoice)
        <p class="text-xs text-gray-500 mt-2">
            Factuur {{ $sentInvoice->invoice_number }} is al verstuurd en wordt wel herrekend. Stuur hem daarna opnieuw,
            anders heeft de klant een ander bedrag in handen dan er in de administratie staat.
        </p>
    @endif
</section>
