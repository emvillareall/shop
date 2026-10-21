<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Booty Fitness') }}</title>
    @stack('styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
@php
    $cartCount = collect(session('carrito', []))->sum(fn ($item) => (int) ($item['cantidad'] ?? 0));
    $showFloatingCart = ! request()->routeIs('ecommerce.carrito.index', 'ecommerce.checkout.index', 'payments.payphone.box');
@endphp
<body class="min-h-screen bg-[#f5f2f8] text-slate-800">
    <x-ecommerce.navbar :cart-count="$cartCount" />
    <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-6 {{ $showFloatingCart ? 'pb-24 sm:pb-16' : '' }}">
        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ session('error') }}
            </div>
        @endif
        @yield('content')
    </main>
    @if($showFloatingCart)
        <x-ecommerce.floating-cart :cart-count="$cartCount" />
    @endif
    <x-ecommerce.footer />
    @livewireScripts
    @stack('scripts')
</body>
</html>
