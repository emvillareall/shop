@php $cartCount = collect(session('carrito', []))->sum(fn($i) => (int) ($i['cantidad'] ?? 0)); @endphp

<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 lg:px-6" x-data="{ open: false }">
        <a href="{{ route('ecommerce.home') }}" class="text-lg font-bold tracking-wide text-brand-800">BOOTY_FITNESS</a>

        <nav class="hidden items-center gap-5 text-sm font-medium text-slate-700 md:flex">
            <a href="{{ route('ecommerce.home') }}" class="hover:text-brand-700">Inicio</a>
            <a href="{{ route('ecommerce.productos.index') }}" class="hover:text-brand-700">Catalogo</a>
            <a href="{{ route('ecommerce.carrito.index') }}" class="inline-flex items-center gap-2 hover:text-brand-700">
                Carrito
                <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-semibold text-brand-700">{{ $cartCount }}</span>
            </a>
            <a href="{{ url('/') }}" class="hover:text-brand-700">Landing</a>
            @guest
                <a href="{{ route('login') }}" class="hover:text-brand-700">Ingresar</a>
            @endguest
        </nav>

        <button type="button" class="btn btn-secondary btn-sm md:hidden" @click="open = !open">Menu</button>
        <div x-show="open" x-cloak class="absolute inset-x-0 top-16 border-b border-slate-200 bg-white p-4 shadow md:hidden">
            <div class="flex flex-col gap-3 text-sm font-medium text-slate-700">
                <a href="{{ route('ecommerce.home') }}">Inicio</a>
                <a href="{{ route('ecommerce.productos.index') }}">Catalogo</a>
                <a href="{{ route('ecommerce.carrito.index') }}" class="inline-flex items-center gap-2">
                    Carrito
                    <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-semibold text-brand-700">{{ $cartCount }}</span>
                </a>
                <a href="{{ url('/') }}">Landing</a>
                @guest
                    <a href="{{ route('login') }}">Ingresar</a>
                @endguest
            </div>
        </div>
    </div>
</header>
