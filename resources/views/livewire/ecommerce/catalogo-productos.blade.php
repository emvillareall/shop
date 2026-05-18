<div class="space-y-5">
    <style>
        .shop-search-wrap {
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
        }
        .shop-search-input {
            flex: 1 1 auto !important;
            min-width: 0 !important;
        }
        .shop-clear-btn {
            position: static !important;
            transform: none !important;
            height: 2rem !important;
            line-height: 1 !important;
            margin: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            white-space: nowrap !important;
        }
    </style>
    @if($mode === 'home')
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="shop-search-wrap">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Buscar por prenda, codigo, color o talla..."
                       class="shop-search-input h-11 rounded-xl border-slate-300 bg-slate-50 text-sm">
                <button type="button"
                        wire:click="clearFilters"
                        class="shop-clear-btn rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-brand-300">
                    Limpiar
                </button>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <button type="button"
                        wire:click.prevent="selectLinea('')"
                        class="rounded-lg border px-2.5 py-1 text-xs font-semibold transition {{ $linea === '' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-brand-300' }}">
                    Todas
                </button>
                @foreach($lineas as $lineaItem)
                    <button type="button"
                            wire:click.prevent="selectLinea({{ (int) $lineaItem->id }})"
                            class="rounded-lg border px-2.5 py-1 text-xs font-semibold transition {{ $linea === (string) $lineaItem->id ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-brand-300' }}">
                        {{ $lineaItem->nombre_linea }}
                    </button>
                @endforeach
            </div>
        </div>

        <section class="space-y-3">
            <div class="flex items-end justify-between gap-2">
                <h2 class="text-xl font-bold">Categorias</h2>
                <p class="text-xs text-slate-500">{{ $categorias->count() }} activas</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button"
                        wire:click.prevent="selectCategoria('')"
                        class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition {{ $categoria === '' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-brand-300' }}">
                    Todas
                </button>
                @foreach($categorias as $cat)
                    <button type="button"
                            wire:click.prevent="selectCategoria({{ (int) $cat->id }})"
                            class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition {{ $categoria === (string) $cat->id ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-brand-300' }}">
                        {{ $cat->nombre_categoria }}
                    </button>
                @endforeach
            </div>
        </section>

        <section>
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="text-xl font-bold">Productos</h2>
                <div wire:loading wire:target="search,linea,categoria" class="inline-flex items-center gap-1 text-xs text-brand-700">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-brand-600"></span>
                    Actualizando...
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse($productos as $producto)
                    <x-ecommerce.product-card :producto="$producto" />
                @empty
                    <div class="col-span-full rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                        No encontramos productos con esos filtros.
                    </div>
                @endforelse
            </div>
        </section>
    @else
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 md:grid-cols-4">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, codigo, color o talla..." class="md:col-span-2">
                <select wire:model.live="linea">
                    <option value="">Todas las lineas</option>
                    @foreach($lineas as $linea)
                        <option value="{{ $linea->id }}">{{ $linea->nombre_linea }}</option>
                    @endforeach
                </select>
                <select wire:model.live="categoria">
                    <option value="">Todas las categorias</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre_categoria }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <p>Resultados actualizados en tiempo real</p>
                <div wire:loading wire:target="search,linea,categoria" class="inline-flex items-center gap-1 text-brand-700">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-brand-600"></span>
                    Filtrando...
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse($productos as $producto)
                <x-ecommerce.product-card :producto="$producto" />
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                    No encontramos productos con esos filtros.
                </div>
            @endforelse
        </div>
    @endif

    @if($productos->hasPages())
        <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
            {{ $productos->links() }}
        </div>
    @endif
</div>
