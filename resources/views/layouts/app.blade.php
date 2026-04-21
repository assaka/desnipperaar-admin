<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DeSnipperaar Admin')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css">
    <script defer src="https://unpkg.com/alpinejs@3/dist/cdn.min.js"></script>
    <style>
        :root { --ink:#0A0A0A; --geel:#F5C518; }
        body { font-family: Arial, Helvetica, sans-serif; background: #EEECE4; color: var(--ink); }
        .brand-bar { background: var(--geel); padding: 14px 24px; font-family: 'Arial Black', Arial, sans-serif; font-weight: 900; font-size: 20px; letter-spacing: 0.04em; }
    </style>
</head>
<body class="min-h-screen">
    <header class="brand-bar flex justify-between items-center">
        <a href="{{ route('orders.index') }}">DESNIPPERAAR ADMIN</a>
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
