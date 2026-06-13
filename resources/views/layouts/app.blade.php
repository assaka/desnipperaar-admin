<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DeSnipperaar Admin')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css">
    <script defer src="https://unpkg.com/alpinejs@3/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
    <style>
        :root { --ink:#0A0A0A; --geel:#F5C518; }
        body { font-family: Arial, Helvetica, sans-serif; background: #EEECE4; color: var(--ink); }
        .brand-bar { background: var(--geel); padding: 14px 24px; font-family: 'Arial Black', Arial, sans-serif; font-weight: 900; font-size: 20px; letter-spacing: 0.04em; }
    </style>
</head>
<body class="min-h-screen">
    <header class="brand-bar flex justify-between items-center">
        <div class="flex gap-6 items-baseline">
            <a href="{{ route('orders.index') }}">DESNIPPERAAR ADMIN</a>
            @auth
                <nav class="text-sm font-normal">
                    <a href="{{ route('orders.index') }}" class="mr-4 {{ request()->routeIs('orders.*') ? 'font-bold underline' : '' }}">Orders</a>
                    <a href="{{ route('planning.index') }}" class="mr-4 {{ request()->routeIs('planning.*') ? 'font-bold underline' : '' }}">Planning</a>
                    @php $reschedCount = \App\Models\Order::whereNotNull('reschedule_requested_at')->count(); @endphp
                    <a href="{{ route('reschedules.index') }}" class="mr-4 {{ request()->routeIs('reschedules.*') ? 'font-bold underline' : '' }}">Herplanningen@if ($reschedCount)<span class="ml-1 bg-orange-500 text-white px-1.5 rounded-full text-xs font-bold">{{ $reschedCount }}</span>@endif</a>
                    <a href="{{ route('offertes.index') }}" class="mr-4 {{ request()->routeIs('offertes.*') ? 'font-bold underline' : '' }}">Offertes</a>
                    <a href="{{ route('invoices.index') }}" class="mr-4 {{ request()->routeIs('facturen.*|invoices.*') ? 'font-bold underline' : '' }}">Facturen</a>
                    <a href="{{ route('customers.index') }}" class="mr-4 {{ request()->routeIs('customers.*') ? 'font-bold underline' : '' }}">Klanten</a>
                    <a href="{{ route('group-deals.index') }}" class="mr-4 {{ request()->routeIs('group-deals.*') ? 'font-bold underline' : '' }}">Groepsdeals</a>
                    <a href="{{ route('drivers.index') }}" class="mr-4 {{ request()->routeIs('drivers.*') ? 'font-bold underline' : '' }}">Chauffeurs</a>
                    <a href="{{ route('coupons.index') }}" class="{{ request()->routeIs('coupons.*') ? 'font-bold underline' : '' }}">Coupons</a>
                </nav>
            @endauth
        </div>
        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-sm underline">Uitloggen ({{ auth()->user()->name }})</button>
            </form>
        @endauth
    </header>
    <main class="max-w-6xl mx-auto p-6 bg-white shadow mt-6">
        @yield('content')
    </main>
</body>
</html>
