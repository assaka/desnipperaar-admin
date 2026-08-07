{{--
    Adres en inhoud van de order aanpassen.

    Ingeklapt, want in de meeste gevallen hoeft er niets te wijzigen en de
    orderpagina is al lang. Openen zet hem uit met de huidige waarden erin.
--}}
@php
    $mediaItems = $order->media_items ?? [];
@endphp

<section class="mb-6" x-data="{ open: {{ $errors->hasAny(['customer_address', 'customer_postcode', 'customer_city', 'box_count', 'container_count']) ? 'true' : 'false' }} }">
    <div class="flex items-baseline gap-3 mb-2">
        <h2 class="font-black">Adres en inhoud</h2>
        <button type="button" @click="open = !open"
                class="bg-gray-200 text-black px-2 py-0.5 text-xs uppercase font-bold"
                x-text="open ? 'Sluiten' : 'Wijzigen'">Wijzigen</button>
    </div>

    @error('box_count')
        <div class="border-l-4 border-red-700 bg-red-50 pl-3 py-2 mb-2 text-sm text-red-800">{{ $message }}</div>
    @enderror

    <div x-show="open" x-cloak>
        @if ($order->isPickedUp())
            <p class="text-xs text-gray-600 bg-yellow-50 border-l-4 border-yellow-400 pl-3 py-2 mb-3">
                Deze order is al opgehaald. De factuur rekent met de aantallen op de bon, dus wat je hier wijzigt
                verandert het factuurbedrag niet.
            </p>
        @endif

        <form method="POST" action="{{ route('orders.details.update', $order) }}">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-2 gap-4 mb-4">
                <label class="text-sm">
                    <span class="block text-gray-600 mb-1">Naam *</span>
                    <input type="text" name="customer_name" required maxlength="150"
                           value="{{ old('customer_name', $order->customer_name) }}"
                           class="w-full border px-2 py-1 text-sm">
                </label>
                <label class="text-sm">
                    <span class="block text-gray-600 mb-1">E-mail *</span>
                    <input type="email" name="customer_email" required maxlength="190"
                           value="{{ old('customer_email', $order->customer_email) }}"
                           class="w-full border px-2 py-1 text-sm">
                </label>
                <label class="text-sm">
                    <span class="block text-gray-600 mb-1">Telefoon</span>
                    <input type="text" name="customer_phone" maxlength="40"
                           value="{{ old('customer_phone', $order->customer_phone) }}"
                           class="w-full border px-2 py-1 text-sm">
                </label>
                <label class="text-sm">
                    <span class="block text-gray-600 mb-1">Adres *</span>
                    <input type="text" name="customer_address" required maxlength="200"
                           value="{{ old('customer_address', $order->customer_address) }}"
                           class="w-full border px-2 py-1 text-sm">
                </label>
                <label class="text-sm">
                    <span class="block text-gray-600 mb-1">Postcode *</span>
                    <input type="text" name="customer_postcode" required maxlength="12"
                           value="{{ old('customer_postcode', $order->customer_postcode) }}"
                           class="w-full border px-2 py-1 text-sm font-mono">
                    @error('customer_postcode') <span class="text-red-700 text-xs">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm">
                    <span class="block text-gray-600 mb-1">Plaats *</span>
                    <input type="text" name="customer_city" required maxlength="100"
                           value="{{ old('customer_city', $order->customer_city) }}"
                           class="w-full border px-2 py-1 text-sm">
                </label>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <label class="text-sm">
                    <span class="block text-gray-600 mb-1">Dozen</span>
                    <input type="number" name="box_count" min="0" max="500"
                           value="{{ old('box_count', (int) $order->box_count) }}"
                           class="w-full border px-2 py-1 text-sm">
                </label>
                <label class="text-sm">
                    <span class="block text-gray-600 mb-1">Rolcontainers 240 L</span>
                    <input type="number" name="container_count" min="0" max="50"
                           value="{{ old('container_count', (int) $order->container_count) }}"
                           class="w-full border px-2 py-1 text-sm">
                </label>
            </div>

            <div class="mb-4">
                <span class="block text-gray-600 text-sm mb-1">Gegevensdragers</span>
                <div class="grid grid-cols-4 gap-3">
                    @foreach (\App\Support\Pricing::MEDIA_LABELS as $key => $label)
                        <label class="text-xs">
                            <span class="block text-gray-600 mb-1">{{ $label }}</span>
                            <input type="number" name="media[{{ $key }}]" min="0" max="10000"
                                   value="{{ old('media.'.$key, (int) ($mediaItems[$key] ?? 0)) }}"
                                   class="w-full border px-2 py-1 text-sm">
                        </label>
                    @endforeach
                </div>
            </div>

            <p class="text-xs text-gray-500 mb-3">
                Een gewijzigde postcode zet de pilotkorting opnieuw en laat de planning het adres opnieuw opzoeken.
                Al afgesproken ophaalkosten blijven staan: die worden niet stil herrekend.
            </p>

            <button type="submit" class="bg-black text-yellow-400 px-4 py-2 text-sm font-bold">Opslaan</button>
        </form>
    </div>
</section>
