@props(['producto'])

@php
    $image = $producto->imageUrl();
    $estadoTexto = $producto->disponibilidadTexto();
    $estadoClase = $producto->disponibilidadBadgeClass();
    $tienePromo = $producto->tienePromocionActiva();
    $precioNormal = $producto->precioNormal();
    $precioFinal = $producto->precioFinal();
    $descuento = $producto->porcentajeDescuento();
@endphp

<article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <a href="{{ route('ecommerce.productos.show', $producto->id) }}" class="block">
        <img src="{{ $image }}"
             alt="{{ $producto->descripcion_producto }}"
             class="h-56 w-full object-cover"
             loading="lazy"
             decoding="async">
    </a>
    <div class="space-y-2 p-4">
        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $estadoClase }}">{{ $estadoTexto }}</span>
        <h3 class="line-clamp-2 text-lg font-semibold text-slate-900">{{ $producto->descripcion_producto }}</h3>
        @if($tienePromo)
            <div class="flex items-center gap-2">
                <p class="text-sm font-semibold text-slate-400 line-through">${{ number_format($precioNormal, 2) }}</p>
                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700">-{{ rtrim(rtrim(number_format($descuento, 2), '0'), '.') }}%</span>
            </div>
            <p class="text-xl font-extrabold text-rose-700">${{ number_format($precioFinal, 2) }}</p>
        @else
            <p class="text-xl font-bold text-brand-700">${{ number_format($precioNormal, 2) }}</p>
        @endif
        <a href="{{ route('ecommerce.productos.show', $producto->id) }}" class="inline-flex rounded-md border border-brand-600 px-3 py-1.5 text-sm font-semibold text-brand-700 hover:bg-brand-50">
            Ver detalle
        </a>
    </div>
</article>
