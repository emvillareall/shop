@php
    $stockData = $producto->coloresStock->map(function($item) use ($reservado) {
        $key = $item->producto_id . '_' . $item->colores_id . '_' . $item->talla_por_color;
        $stockReservado = $reservado[$key] ?? 0;
        return [
            'color_id' => (int) $item->colores_id,
            'talla' => $item->talla_por_color,
            'stock' => max(0, $item->stock_por_color - $stockReservado),
        ];
    })->values();

    $currentPrice = (float) ($producto->precio_venta_producto ?? 0);
    $fallbackOldPrice = (float) ($producto->precio_pesos_producto ?? 0);
    $safeOldPrice = $fallbackOldPrice > $currentPrice ? $fallbackOldPrice : null;
    $badgeLabel = $producto->agotado ? 'Agotado' : ($safeOldPrice ? 'Oferta' : 'Nuevo');
    $rating = (int) round((float) data_get($producto, 'rating', 4));
    $rating = max(1, min(5, $rating));
@endphp

<article class="h-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
         x-data="{
            open:false,
            vote:0,
            colorId:null,
            talla:'',
            baseRating: {{ $rating }},
            stockData: @js($stockData),
            tallasPorColor(id){ return this.stockData.filter(s => s.color_id === Number(id)); },
            shownRating(){ return Math.max(1, Math.min(5, this.baseRating + this.vote)); }
         }">
    <div class="relative h-48 overflow-hidden bg-slate-100">
        <img src="{{ $producto->imagen_producto ? asset('storage/' . $producto->imagen_producto) : 'https://via.placeholder.com/600x800/f3f4f6/94a3b8?text=Producto' }}"
             alt="{{ $producto->descripcion_producto }}"
             class="h-full w-full object-cover">
        <span class="absolute right-3 top-3 rounded-md bg-purple-500 px-2.5 py-1 text-[11px] font-semibold text-white">{{ $badgeLabel }}</span>
    </div>

    <div class="flex min-h-[190px] flex-col space-y-2 p-4">
        <h3 class="line-clamp-2 min-h-[52px] text-[1.05rem] font-semibold leading-6 text-slate-900">{{ $producto->descripcion_producto }}</h3>
        <div class="flex items-end gap-2">
            <p class="text-[1.8rem] font-bold text-purple-700">${{ number_format($currentPrice, 2) }}</p>
            @if($safeOldPrice)
                <p class="pb-1 text-xs font-semibold text-slate-400 line-through">${{ number_format($safeOldPrice, 2) }}</p>
            @endif
        </div>
        <div class="flex items-center gap-1 text-sm">
            <template x-for="i in 5" :key="i">
                <span :class="i <= shownRating() ? 'text-amber-400' : 'text-slate-300'">&starf;</span>
            </template>
        </div>
        <div class="mt-auto flex items-center justify-start gap-1 pt-2">
            <button type="button"
                    class="rounded-md border px-2 py-1 text-xs transition"
                    :class="vote === 1 ? 'border-purple-600 bg-purple-50 text-purple-700' : 'border-slate-300 text-slate-500 hover:border-purple-300'"
                    @click="vote = (vote === 1 ? 0 : 1)"
                    aria-label="Like">
                &#128077;
            </button>
            <button type="button"
                    class="rounded-md border px-2 py-1 text-xs transition"
                    :class="vote === -1 ? 'border-purple-600 bg-purple-50 text-purple-700' : 'border-slate-300 text-slate-500 hover:border-purple-300'"
                    @click="vote = (vote === -1 ? 0 : -1)"
                    aria-label="Dislike">
                &#128078;
            </button>
        </div>

        @if(!$producto->agotado)
            <button type="button"
                    class="mt-2 w-full rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-700"
                    @click="open = true"
                    aria-label="Abrir selector de compra">
                &#128722;
            </button>
        @endif
    </div>

    <template x-teleport="body">
        <div x-show="open"
             x-transition.opacity
             x-cloak
             class="fixed inset-0 z-[9999] bg-black/75"
             @keydown.escape.window="open = false">
            <div class="flex h-full w-full items-center justify-center p-4">
                <div @click.away="open = false"
                     class="relative w-full max-w-[860px] overflow-hidden rounded-lg border border-slate-300 bg-white shadow-[0_22px_60px_rgba(0,0,0,.55)]">
                    <button type="button"
                            class="absolute right-3 top-3 z-20 inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                            @click="open = false"
                            aria-label="Cerrar">
                        ✕
                    </button>

                    <div class="grid md:grid-cols-[1fr_1fr]">
                        <div class="border-b border-slate-200 bg-slate-100 md:border-b-0 md:border-r">
                            <img src="{{ asset('storage/' . $producto->imagen_producto) }}"
                                 alt="{{ $producto->descripcion_producto }}"
                                 class="h-[240px] w-full object-cover md:h-[470px]">
                        </div>

                        <div class="space-y-4 p-5 md:p-6">
                            <div>
                                <h4 class="text-4xl font-semibold leading-tight text-slate-800">{{ $producto->descripcion_producto }}</h4>
                                <p class="mt-1 text-3xl font-bold text-emerald-600">${{ number_format($producto->precio_venta_producto, 2) }}</p>
                                <p class="mt-1 text-sm text-slate-600"><span class="font-semibold">Disponibilidad:</span> {{ $producto->disponibilidadTexto() }}</p>
                            </div>

                            <div class="border-t border-slate-200 pt-4">
                                <p class="mb-2 text-sm font-semibold text-slate-700">Colores disponibles:</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($producto->coloresStock->groupBy('colores_id') as $items)
                                        @php $color = $items->first()->color; @endphp
                                        @if($color)
                                            <button type="button"
                                                    class="h-10 w-10 rounded-full border-2 transition"
                                                    :class="colorId === {{ $color->id }} ? 'border-slate-900 ring-2 ring-slate-200' : 'border-slate-300 hover:border-slate-400'"
                                                    style="background-color: {{ $color->codigo_color }};"
                                                    @click="colorId = {{ $color->id }}; talla = ''"
                                                    title="{{ $color->nombre_color }}"></button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div class="border-t border-slate-200 pt-4">
                                <p class="mb-2 text-sm font-semibold text-slate-700">Tallas disponibles:</p>
                                <div class="flex flex-wrap gap-2" x-show="colorId">
                                    <template x-for="item in tallasPorColor(colorId)" :key="item.talla">
                                        <button type="button"
                                                class="rounded-md border px-3 py-1.5 text-xs font-semibold transition"
                                                :class="talla === item.talla ? 'border-slate-700 bg-slate-700 text-white' : 'border-slate-300 text-slate-700 hover:border-slate-400'"
                                                @click="talla = item.talla"
                                                x-text="item.talla + ' (' + item.stock + ')'"></button>
                                    </template>
                                </div>
                                <p x-show="!colorId" class="text-xs text-slate-500">Selecciona un color para ver tallas.</p>
                            </div>

                            <form method="POST" action="{{ route('ecommerce.carrito.agregar') }}" class="space-y-3 border-t border-slate-200 pt-4">
                                @csrf
                                <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                                <input type="hidden" name="color_id" :value="colorId">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Talla seleccionada</label>
                                    <input type="text" name="talla" x-model="talla" readonly required class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Cantidad</label>
                                    <input type="number" name="cantidad" min="1" value="1" required class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm">
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="inline-flex items-center rounded-md bg-slate-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                        &#128722; Agregar al carrito
                                    </button>
                                    <button type="button" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" @click="open = false">
                                        Cerrar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</article>
