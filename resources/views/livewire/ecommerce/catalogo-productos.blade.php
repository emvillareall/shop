<div class="space-y-5">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o codigo..." class="md:col-span-2">
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

    @if($productos->hasPages())
        <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
            {{ $productos->links() }}
        </div>
    @endif
</div>
