@props(['producto'])

@php
    $image = $producto->imageUrl();
    $estadoTexto = $producto->disponibilidadTexto();
    $estadoClase = $producto->disponibilidadBadgeClass();
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
        <p class="text-xl font-bold text-brand-700">${{ number_format($producto->precio_venta_producto, 2) }}</p>
        <a href="{{ route('ecommerce.productos.show', $producto->id) }}" class="inline-flex rounded-md border border-brand-600 px-3 py-1.5 text-sm font-semibold text-brand-700 hover:bg-brand-50">
            Ver detalle
        </a>
    </div>
</article>
