<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Booty Fitness') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f4fa] font-['Manrope'] text-slate-800">
    <header class="sticky top-0 z-40 border-b border-brand-100 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 lg:px-6">
            <a href="{{ route('ecommerce.home') }}" class="text-base font-extrabold tracking-[0.08em] text-brand-800">BOOTY_FITNESS</a>
            <div class="flex items-center gap-2">
                <a href="{{ route('ecommerce.productos.index') }}" class="btn btn-sm btn-secondary">Tienda</a>
                <a href="{{ route('ecommerce.carrito.index') }}" class="btn btn-sm btn-primary">Carrito</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
        @yield('content')
    </main>

    <footer class="border-t border-brand-100 bg-white py-5">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 text-sm text-slate-500 lg:flex-row lg:px-6">
            <p>{{ config('app.name') }} © {{ date('Y') }}</p>
            <p>Moda femenina premium | Compra online segura</p>
        </div>
    </footer>
</body>
</html>
