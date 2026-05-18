@extends('layouts.ecommerce', ['title' => 'Producto | Booty Fitness'])

@section('content')
    <style>
        .product-main-frame {
            width: 100%;
            max-width: 26rem;
            height: clamp(22rem, 55vh, 34rem);
            margin-inline: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 0.5rem;
            background: #fff;
        }

        .product-main-image {
            width: 100%;
            height: 100%;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain !important;
            object-position: center;
            display: block;
        }
    </style>

    <x-ecommerce.breadcrumb :items="[
        ['label' => 'Inicio', 'url' => route('ecommerce.home')],
        ['label' => 'Catalogo', 'url' => route('ecommerce.productos.index')],
        ['label' => $producto->descripcion_producto],
    ]" />

    @php
        $stockData = $producto->coloresStock->map(fn($item) => [
            'color_id' => (int) $item->colores_id,
            'talla' => $item->talla_por_color,
            'stock' => (int) $item->stock_por_color,
            'color_nombre' => $item->color->nombre_color ?? 'Color',
            'codigo_color' => $item->color->codigo_color ?? '#111827',
        ])->values();
        $disponibilidad = $producto->disponibilidadTexto();
        $disponibilidadClase = $producto->disponibilidadTextClass();
        $defaultImages = $producto->allImageUrls();
        $imagesByColor = [];
        $imageMeta = [];
        $imagenesActivas = $producto->imagenes()->where('activo', true)->orderByDesc('es_principal')->orderBy('orden')->get();
        foreach ($producto->coloresStock->groupBy('colores_id') as $colorId => $itemsColor) {
            $urls = $imagenesActivas
                ->where('color_id', (int) $colorId)
                ->pluck('ruta')
                ->filter()
                ->map(fn ($ruta) => route('media.producto', ['filename' => ltrim((string) $ruta, '/')]))
                ->unique()
                ->values()
                ->all();
            $imagesByColor[(int) $colorId] = $urls;
            foreach ($urls as $url) {
                $imageMeta[] = [
                    'url' => $url,
                    'color_id' => (int) $colorId,
                ];
            }
        }
        $allThumbs = collect($defaultImages)
            ->filter()
            ->unique()
            ->values()
            ->all();
        if (empty($allThumbs)) {
            $allThumbs = collect($imageMeta)->pluck('url')->filter()->unique()->values()->all();
        }
        $fallbackImage = $producto->imageUrl();
        $tienePromo = $producto->tienePromocionActiva();
        $precioNormal = $producto->precioNormal();
        $precioFinal = $producto->precioFinal();
        $descuentoPromo = $producto->porcentajeDescuento();
        $etiquetaPromo = trim((string) ($producto->promocion_etiqueta ?? ''));
        $imageColorMap = [];
        foreach ($imageMeta as $meta) {
            $url = (string) ($meta['url'] ?? '');
            $colorId = $meta['color_id'] ?? null;
            if ($url === '' || $colorId === null) {
                continue;
            }
            $imageColorMap[$url] ??= [];
            $imageColorMap[$url][] = (int) $colorId;
        }
        foreach ($imageColorMap as $url => $ids) {
            $imageColorMap[$url] = array_values(array_unique($ids));
        }
        $whatsappRaw = (string) config('services.whatsapp.support_number', '');
        $whatsappNumber = preg_replace('/\D+/', '', $whatsappRaw);
        $whatsappMessage = rawurlencode(
            'Hola, tengo una consulta sobre este producto: ' .
            $producto->descripcion_producto .
            ' ($' . number_format((float) $precioFinal, 2) . '). ' .
            route('ecommerce.productos.show', $producto->id)
        );
        $whatsappUrl = $whatsappNumber !== ''
            ? "https://wa.me/{$whatsappNumber}?text={$whatsappMessage}"
            : '';
    @endphp

    <div class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-2 lg:p-6"
         x-data="{
            colorId: null,
            talla: '',
            cantidad: 1,
            stockData: @js($stockData),
            defaultImages: @js($defaultImages),
            imagesByColor: @js($imagesByColor),
            imageMeta: @js($imageMeta),
            allThumbs: @js($allThumbs),
            imageColorMap: @js($imageColorMap),
            fallbackImage: @js($fallbackImage),
            activeImage: '',
            syncingFromThumb: false,
            tallas() { return this.stockData.filter(i => i.color_id === Number(this.colorId)); },
            imagesForCurrentColor() {
                if (!this.colorId) return [];
                const byColor = this.imagesByColor[String(this.colorId)] || this.imagesByColor[Number(this.colorId)] || [];
                return Array.isArray(byColor) && byColor.length ? byColor : [];
            },
            stockSeleccionado() {
                const hit = this.stockData.find(i => i.color_id === Number(this.colorId) && i.talla === this.talla);
                return hit ? Number(hit.stock) : 0;
            },
            puedeComprar() {
                return this.stockSeleccionado() > 0 && this.talla !== '' && this.colorId !== null && this.cantidad > 0;
            },
            colorName() {
                if (!this.colorId) return '';
                const found = this.stockData.find(i => i.color_id === Number(this.colorId));
                return found ? (found.color_nombre || '') : '';
            },
            init() {
                this.activeImage = this.allThumbs.length ? this.allThumbs[0] : this.fallbackImage;
                this.$watch('colorId', () => {
                    const list = this.imagesForCurrentColor();
                    if (list && list.length) {
                        if (this.syncingFromThumb && this.activeImage && list.includes(this.activeImage)) {
                            this.syncingFromThumb = false;
                            return;
                        }
                        this.syncingFromThumb = false;
                        this.activeImage = list[0];
                    }
                });
            },
            syncColorFromImage(url) {
                const ids = this.imageColorMap[url] || [];
                if (!Array.isArray(ids) || ids.length === 0) return;
                const current = Number(this.colorId || 0);
                const nextColorId = ids.includes(current) ? current : Number(ids[0] || 0);
                if (nextColorId > 0 && current !== nextColorId) {
                    this.syncingFromThumb = true;
                    this.colorId = nextColorId;
                    this.talla = '';
                }
            },
            setActiveImage(url, maybeColorId = null) {
                this.activeImage = url;
                const parsed = Number(maybeColorId);
                if (!Number.isNaN(parsed) && parsed > 0 && this.colorId !== parsed) {
                    this.colorId = parsed;
                    this.talla = '';
                }
            }
         }">
        <div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <div class="product-main-frame">
                        <img :src="activeImage || fallbackImage"
                             alt="{{ $producto->descripcion_producto }}"
                             class="product-main-image"
                             loading="lazy"
                             decoding="async">
                    </div>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <template x-for="img in allThumbs" :key="img">
                    <button type="button"
                            @click="
                                setActiveImage(img, null);
                                syncColorFromImage(img);
                            "
                            class="h-16 w-16 overflow-hidden rounded-md border border-slate-200 transition hover:border-brand-500"
                            :class="activeImage === img ? 'ring-2 ring-brand-500' : ''">
                        <img :src="img" class="h-full w-full object-cover" alt="Miniatura producto">
                    </button>
                </template>
            </div>
        </div>

        <div class="space-y-5">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">{{ $producto->descripcion_producto }}</h1>
                @if($tienePromo)
                    <div class="mt-2 flex items-center gap-2">
                        <p class="text-lg font-semibold text-slate-400 line-through">${{ number_format($precioNormal, 2) }}</p>
                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-700">-{{ rtrim(rtrim(number_format($descuentoPromo, 2), '0'), '.') }}%</span>
                        @if($etiquetaPromo !== '')
                            <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-bold text-brand-700">{{ $etiquetaPromo }}</span>
                        @endif
                    </div>
                    <p class="text-3xl font-extrabold text-rose-700">${{ number_format($precioFinal, 2) }}</p>
                @else
                    <p class="mt-2 text-3xl font-extrabold text-brand-700">${{ number_format($precioNormal, 2) }}</p>
                @endif
                <p class="mt-2 text-sm font-semibold {{ $disponibilidadClase }}">Disponibilidad: {{ $disponibilidad }}</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="mb-2 text-sm font-semibold">Colores disponibles</p>
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    @foreach($producto->coloresStock->groupBy('colores_id') as $items)
                        @php $color = $items->first()->color; @endphp
                        @if($color)
                            <button type="button"
                                    @click="colorId = {{ $color->id }}; talla='';"
                                    :class="colorId === {{ $color->id }} ? 'ring-2 ring-brand-500 border-brand-500' : ''"
                                    class="h-9 w-9 rounded-full border border-slate-300 transition"
                                    style="background-color: {{ $color->codigo_color }};"
                                    title="{{ $color->nombre_color }}"></button>
                        @endif
                    @endforeach
                    <span x-show="colorName()" class="ml-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600" x-text="colorName()"></span>
                </div>

                <p class="mb-2 text-sm font-semibold">Tallas disponibles</p>
                <div class="flex flex-wrap gap-2" x-show="colorId">
                    <template x-for="item in tallas()" :key="item.talla">
                        <button type="button"
                                @click="talla = item.talla"
                                :class="talla === item.talla ? 'bg-slate-800 text-white' : 'bg-white text-slate-700 border border-slate-300'"
                                class="rounded-md px-3 py-1.5 text-sm font-semibold transition"
                                :disabled="item.stock < 1"
                                x-text="item.talla + ' (' + item.stock + ')'"></button>
                    </template>
                </div>
                <p x-show="!colorId" class="text-xs text-slate-500">Selecciona un color para ver tallas.</p>
            </div>

            <form action="{{ route('ecommerce.carrito.agregar') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                <input type="hidden" name="color_id" :value="colorId">
                <div>
                    <label class="mb-1 block text-sm font-semibold">Talla seleccionada</label>
                    <input type="text" name="talla" x-model="talla" readonly required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Cantidad</label>
                    <input type="number" name="cantidad" x-model.number="cantidad" min="1" value="1" required>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary" :disabled="!puedeComprar()">Agregar al carrito</button>
                    <a href="{{ route('ecommerce.productos.index') }}" class="btn btn-secondary">Volver</a>
                    @if($whatsappUrl !== '')
                        <a href="{{ $whatsappUrl }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                           style="background-color:#16a34a;border:1px solid #15803d;color:#fff;">
                            Consultar por WhatsApp
                        </a>
                    @else
                        <button type="button"
                                class="inline-flex cursor-not-allowed items-center rounded-md border border-amber-400 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700"
                                title="Configura WHATSAPP_SUPPORT_NUMBER en el entorno para activarlo"
                                disabled>
                            WhatsApp no configurado
                        </button>
                    @endif
                </div>
                <p class="text-xs text-slate-500" x-show="talla && stockSeleccionado() > 0">Stock de esta variante: <span x-text="stockSeleccionado()"></span></p>
                <p class="text-xs text-rose-500" x-show="talla && stockSeleccionado() < 1">La talla seleccionada esta agotada.</p>
                <p class="text-xs text-slate-500" x-show="!talla || !colorId">Elige color y talla para continuar.</p>
            </form>
        </div>
    </div>
@endsection
