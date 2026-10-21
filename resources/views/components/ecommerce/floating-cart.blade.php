@props(['cartCount' => 0])

@php($cartCount = (int) $cartCount)

<a href="{{ route('ecommerce.carrito.index') }}"
   aria-label="Abrir carrito de compras{{ $cartCount > 0 ? ', ' . $cartCount . ' artículos' : '' }}"
   title="Ver carrito"
   data-floating-cart
   class="fixed z-30 inline-flex h-14 w-14 items-center justify-center rounded-full bg-brand-700 text-white shadow-lg shadow-brand-900/25 transition duration-200 hover:-translate-y-0.5 hover:bg-brand-800 hover:shadow-xl focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-200 focus-visible:ring-offset-2 active:translate-y-0 active:scale-95 md:h-auto md:w-auto md:gap-2.5 md:px-4 md:py-3"
   style="right: calc(env(safe-area-inset-right, 0px) + 1rem); bottom: calc(env(safe-area-inset-bottom, 0px) + 1rem);">
    <span class="relative inline-flex">
        <svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 1.9-1.4L21 8H6" />
            <circle cx="10" cy="19" r="1.4" />
            <circle cx="18" cy="19" r="1.4" />
        </svg>
        @if($cartCount > 0)
            <span aria-hidden="true" class="absolute -right-2 -top-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full border-2 border-brand-700 bg-white px-1 text-[10px] font-bold leading-none text-brand-800 md:hidden">
                {{ $cartCount > 99 ? '99+' : $cartCount }}
            </span>
        @endif
    </span>
    <span class="sr-only md:not-sr-only md:text-sm md:font-semibold">Carrito</span>
    @if($cartCount > 0)
        <span aria-hidden="true" class="hidden min-w-5 items-center justify-center rounded-full bg-white/15 px-1.5 py-0.5 text-xs font-semibold md:inline-flex">
            {{ $cartCount > 99 ? '99+' : $cartCount }}
        </span>
    @endif
</a>
