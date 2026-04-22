@extends('layouts.app')
@section('title', $certificate->certificate_number)

@section('content')
    @php
        $order       = $certificate->order;
        $customer    = $order->customer;
        $bon         = $order->bons->first();
        $mediaSource = !empty($bon?->actual_media) ? $bon->actual_media : ($order->media_items ?? []);
        $mediaInt    = fn($k) => (int) ($mediaSource[$k] ?? 0);
        $method      = strtoupper((string) $certificate->destruction_method);
    @endphp

    <div class="flex justify-between items-baseline mb-4">
        <div>
            <h1 class="text-2xl font-black font-mono">{{ $certificate->certificate_number }}</h1>
            <div class="text-sm text-gray-600">
                Vernietigingscertificaat &middot;
                <a href="{{ route('orders.show', $order) }}" class="underline">{{ $order->order_number }}</a>
                @if ($bon) &middot; <a href="{{ route('bons.show', $bon) }}" class="underline">{{ $bon->bon_number }}</a>@endif
                @if ($certificate->emailed_at)
                    · <span class="bg-green-700 text-white px-2 py-0.5 text-xs font-bold uppercase">verzonden {{ $certificate->emailed_at->format('Y-m-d H:i') }}</span>
                @endif
            </div>
        </div>
        <div class="flex gap-3 text-sm">
            <a href="{{ route('certificates.pdf', $certificate) }}" target="_blank" class="bg-black text-yellow-400 px-3 py-1.5 text-xs uppercase font-bold">Print / PDF</a>
            @if (!$certificate->emailed_at)
                <form method="POST" action="{{ route('certificates.mail', $certificate) }}" class="inline">
                    @csrf
                    <button class="bg-yellow-400 text-black px-3 py-1.5 text-xs uppercase font-bold">Mail naar klant</button>
                </form>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 mb-4 text-sm">{{ session('status') }}</div>
    @endif

    <section class="grid grid-cols-2 gap-6 mb-6 bg-gray-50 border-l-4 border-yellow-400 p-4">
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Datum</div>
            <div class="font-mono text-lg">{{ $certificate->destroyed_at?->format('d-m-Y') ?? '—' }}</div>
        </div>
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Tijdstip</div>
            <div class="font-mono text-lg">{{ $certificate->destroyed_at?->format('H:i') ?? '—' }}</div>
        </div>
    </section>

    <section class="mb-6">
        <h2 class="font-black mb-2 text-lg">Opdrachtgever</h2>
        <div class="text-sm grid grid-cols-2 gap-x-6 gap-y-1">
            <div><span class="text-gray-500">Bedrijf / organisatie:</span> <strong>{{ $customer?->company ?: '—' }}</strong></div>
            <div><span class="text-gray-500">Contactpersoon:</span> <strong>{{ $order->customer_name }}</strong></div>
            <div class="col-span-2"><span class="text-gray-500">Adres:</span> <strong>{{ trim($order->customer_address . ', ' . $order->customer_postcode . ' ' . $order->customer_city, ', ') }}</strong></div>
            <div><span class="text-gray-500">E-mail:</span> <strong>{{ $order->customer_email }}</strong></div>
            <div><span class="text-gray-500">Telefoon:</span> <strong>{{ $order->customer_phone ?? '—' }}</strong></div>
            <div><span class="text-gray-500">KvK-nummer:</span> <strong>{{ $customer?->kvk ?? '—' }}</strong></div>
            @if ($order->customer_reference)
                <div><span class="text-gray-500">Klantreferentie:</span> <strong class="font-mono">{{ $order->customer_reference }}</strong></div>
            @endif
        </div>
    </section>

    @if ($bon)
        <section class="mb-6">
            <h2 class="font-black mb-2 text-lg">Ophaal / Chain of Custody</h2>
            <div class="text-sm grid grid-cols-2 gap-x-6 gap-y-1">
                <div><span class="text-gray-500">Leveringsmethode:</span> <strong>{{ ucfirst($bon->mode) }}service</strong></div>
                <div><span class="text-gray-500">Datum aanlevering:</span> <strong>{{ $bon->picked_up_at?->format('d-m-Y H:i') ?? '—' }}</strong></div>
                <div class="col-span-2"><span class="text-gray-500">Chauffeur:</span> <strong>{{ $bon->driver_name_snapshot ?? '—' }}</strong>
                    @if ($bon->driver_license_last4) <span class="font-mono text-xs">(rijbewijs ****{{ $bon->driver_license_last4 }})</span>@endif
                </div>
                <div class="col-span-2"><span class="text-gray-500">Verzegelde containers / sealnummers:</span>
                    @if ($bon->seals->count())
                        <strong class="font-mono">{{ $bon->seals->pluck('seal_number')->implode(' · ') }}</strong>
                    @else
                        <strong>—</strong>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <section class="mb-6">
        <h2 class="font-black mb-2 text-lg">Vernietigde materialen</h2>
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-black">
                    <th class="text-left py-2 px-3 font-bold uppercase text-xs tracking-wider">Materiaal</th>
                    <th class="text-right py-2 px-3 font-bold uppercase text-xs tracking-wider">Aantal</th>
                    <th class="text-left py-2 px-3 font-bold uppercase text-xs tracking-wider">Methode</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $hasPaperMethod = str_contains($method, 'P-4') || str_contains($method, 'P-5') || str_contains($method, 'P-6');
                @endphp
                <tr class="border-b">
                    <td class="py-2 px-3">Papier / dossiers</td>
                    <td class="text-right py-2 px-3 font-mono">
                        @if ($certificate->weight_kg_final){{ number_format($certificate->weight_kg_final, 1, ',', '.') }} kg<br>@endif
                        {{ $order->container_count }} rolcontainer(s), {{ $order->box_count }} doos/dozen
                    </td>
                    <td class="py-2 px-3 font-mono">
                        @foreach (['P-4', 'P-5', 'P-6'] as $m)
                            @php $on = str_contains($method, $m) || ($m === 'P-5' && !$hasPaperMethod); @endphp
                            <span class="{{ $on ? 'bg-yellow-400 text-black font-bold px-1' : 'text-gray-400' }}">{{ $m }}</span>
                        @endforeach
                    </td>
                </tr>
                @php
                    $mediaRows = [
                        'hdd'    => ['Harde schijven (HDD)', ['H-3', 'H-4', 'H-5']],
                        'ssd'    => ['SSDs',                 ['E-3', 'E-4']],
                        'phone'  => ['Mobiele telefoons',    ['E-3', 'E-4']],
                        'usb'    => ['USB / geheugenkaarten',['E-3', 'E-4']],
                        'laptop' => ['Anders (laptop)',      []],
                    ];
                @endphp
                @foreach ($mediaRows as $k => [$label, $methods])
                    @if ($mediaInt($k) > 0)
                        <tr class="border-b">
                            <td class="py-2 px-3">{{ $label }}</td>
                            <td class="text-right py-2 px-3 font-mono">{{ $mediaInt($k) }} stuks</td>
                            <td class="py-2 px-3 font-mono">
                                @foreach ($methods as $m)
                                    <span class="{{ str_contains($method, $m) ? 'bg-yellow-400 text-black font-bold px-1' : 'text-gray-400' }}">{{ $m }}</span>
                                @endforeach
                                @if (empty($methods))<span class="text-gray-400">—</span>@endif
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        <p class="text-xs text-gray-500 mt-2">Gemarkeerde methode is de vernietigingsmethode uit het certificaat (‘{{ $certificate->destruction_method }}’). Voor individuele serienummers / IMEI zie Bijlage A van de PDF.</p>
    </section>

    <section class="mb-6">
        <h2 class="font-black mb-2 text-lg">Locatie van vernietiging</h2>
        @php $locOnsite = $bon && $bon->mode === 'mobiel'; @endphp
        <div class="text-sm">
            @if ($locOnsite)
                <span class="bg-yellow-400 text-black px-2 py-0.5 font-bold">☑ Mobiel op locatie opdrachtgever</span>
            @else
                <span class="bg-yellow-400 text-black px-2 py-0.5 font-bold">☑ Bij depot Amsterdam-Noord (1034DN)</span>
            @endif
        </div>
    </section>

    <section class="mb-6 bg-green-50 border-l-4 border-green-600 p-4">
        <p class="text-sm">
            <strong>DeSnipperaar bevestigt hierbij</strong> dat de bovenstaande materialen volledig en onomkeerbaar zijn vernietigd,
            conform AVG, DIN 66399, NEN-15713 en ISO 21964.
        </p>
    </section>

    <section class="grid grid-cols-2 gap-6 mb-6">
        <div class="border border-black p-3">
            <div class="text-xs font-bold uppercase tracking-wider mb-2">Operator (DeSnipperaar)</div>
            <div class="text-sm"><span class="text-gray-500">Naam:</span> <strong>{{ $certificate->operator_name ?? '—' }}</strong></div>
            <div class="text-sm"><span class="text-gray-500">Datum:</span> <strong>{{ $certificate->destroyed_at?->format('d-m-Y') ?? '—' }}</strong></div>
            <div class="text-sm mt-2"><span class="text-gray-500">Handtekening:</span>
                @if ($certificate->operator_signature_path || $bon?->driver_signature_path)
                    <span class="text-green-700 font-bold">✓ ondertekend</span>
                @else
                    <span class="text-gray-400">— niet ondertekend —</span>
                @endif
            </div>
        </div>
        <div class="border border-black p-3">
            <div class="text-xs font-bold uppercase tracking-wider mb-2">Opdrachtgever / getuige</div>
            <div class="text-sm"><span class="text-gray-500">Naam:</span> <strong>{{ $order->customer_name }}</strong></div>
            <div class="text-sm"><span class="text-gray-500">Datum:</span> <strong>{{ $bon?->picked_up_at?->format('d-m-Y') ?? '—' }}</strong></div>
            <div class="text-sm mt-2"><span class="text-gray-500">Handtekening:</span>
                @if ($bon?->customer_signature_path)
                    <span class="text-green-700 font-bold">✓ ondertekend op bon</span>
                @else
                    <span class="text-gray-400">— niet ondertekend —</span>
                @endif
            </div>
        </div>
    </section>
@endsection
