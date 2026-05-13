<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
        <style>
            .productos-filtros-grid {
                display: grid !important;
                grid-template-columns: 2.2fr 1.4fr 1.4fr 1.2fr .9fr .9fr auto !important;
                gap: 8px !important;
                align-items: center !important;
            }
            .productos-filtro-control {
                height: 34px !important;
                min-height: 34px !important;
                padding: 4px 10px !important;
                font-size: 13px !important;
                line-height: 1.2 !important;
                margin: 0 !important;
                border-radius: 8px !important;
            }
            .productos-filtro-btn {
                height: 34px !important;
                padding: 4px 12px !important;
                font-size: 13px !important;
                white-space: nowrap !important;
            }
            @media (max-width: 1200px) {
                .productos-filtros-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                }
            }
            @media (max-width: 768px) {
                .productos-filtros-grid {
                    grid-template-columns: 1fr !important;
                }
            }
        </style>
        <form wire:submit.prevent="aplicarFiltros" class="productos-filtros-grid">
            <input type="text"
                   wire:model.defer="search"
                   placeholder="Buscar por código o descripción..."
                   class="productos-filtro-control rounded-md border border-slate-300">

            <select wire:model.defer="linea" class="productos-filtro-control rounded-md border border-slate-300">
                <option value="">Todas las líneas</option>
                @foreach($lineas as $lineaOpt)
                    <option value="{{ $lineaOpt->id }}">{{ $lineaOpt->nombre_linea }}</option>
                @endforeach
            </select>

            <select wire:model.defer="categoria" class="productos-filtro-control rounded-md border border-slate-300">
                <option value="">Todas las categorías</option>
                @foreach($categorias as $categoriaOpt)
                    <option value="{{ $categoriaOpt->id }}">{{ $categoriaOpt->nombre_categoria }}</option>
                @endforeach
            </select>

            <select wire:model.defer="estadoStock" class="productos-filtro-control rounded-md border border-slate-300">
                <option value="">Stock: todos</option>
                <option value="disponible">Disponible</option>
                <option value="poco">Pocas unidades</option>
                <option value="agotado">Agotado</option>
            </select>

            <input type="number" step="0.01" min="0" wire:model.defer="precioMin"
                   placeholder="Precio mín."
                   class="productos-filtro-control rounded-md border border-slate-300">

            <input type="number" step="0.01" min="0" wire:model.defer="precioMax"
                   placeholder="Precio máx."
                   class="productos-filtro-control rounded-md border border-slate-300">

            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-primary btn-sm productos-filtro-btn">Aplicar</button>
                <button type="button" wire:click="limpiarFiltros" class="btn btn-light btn-sm productos-filtro-btn">Limpiar</button>
            </div>
        </form>

        <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs text-slate-500" wire:loading wire:target="search,linea,categoria,estadoStock,precioMin,precioMax">
                Filtrando productos...
            </div>
            <div class="text-xs text-slate-500">
                <span class="font-semibold">Filtros activos:</span>
                {{ $appliedSearch !== '' ? ' búsqueda' : '' }}
                {{ $appliedLinea !== '' ? ' línea' : '' }}
                {{ $appliedCategoria !== '' ? ' categoría' : '' }}
                {{ $appliedEstadoStock !== '' ? ' stock' : '' }}
                {{ ($appliedPrecioMin !== '' || $appliedPrecioMax !== '') ? ' precio' : '' }}
                @if($appliedSearch === '' && $appliedLinea === '' && $appliedCategoria === '' && $appliedEstadoStock === '' && $appliedPrecioMin === '' && $appliedPrecioMax === '')
                    ninguno
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('compras.index') }}" class="btn btn-secondary btn-sm">Volver a Compras</a>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Imagen</th>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Stock por color/talla</th>
                    <th>Precio (₱)</th>
                    <th>Precio ($)</th>
                    <th>Venta</th>
                    <th>Compra ID</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($productos as $producto)
                    <tr>
                        <td>{{ $producto->id }}</td>
                        <td>
                            <img src="{{ $producto->imageUrl() }}"
                                 alt="{{ $producto->codigo_producto }}"
                                 class="h-14 w-14 rounded-md object-cover"
                                 loading="lazy">
                        </td>
                        <td>{{ $producto->codigo_producto }}</td>
                        <td>{{ $producto->descripcion_producto }}</td>
                        <td>
                            @if($producto->coloresStock->count())
                                <div class="flex flex-wrap gap-1">
                                    @foreach($producto->coloresStock as $detalle)
                                        @php
                                            $bg = $detalle->color->codigo_color ?? '#e2e8f0';
                                            $tx = (hexdec(substr($bg,1,2))*0.299 + hexdec(substr($bg,3,2))*0.587 + hexdec(substr($bg,5,2))*0.114) < 186 ? '#fff' : '#000';
                                        @endphp
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                                              style="background-color: {{ $bg }}; color: {{ $tx }};"
                                              title="Color: {{ $detalle->color->nombre_color ?? 'N/A' }} | Talla: {{ $detalle->talla_por_color }} | Stock: {{ $detalle->stock_por_color }}">
                                            {{ $detalle->talla_por_color }}: {{ $detalle->stock_por_color }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-slate-500">Sin stock</span>
                            @endif
                        </td>
                        <td>${{ number_format($producto->precio_pesos_producto, 2) }}</td>
                        <td>${{ number_format($producto->precio_dolares_producto, 2) }}</td>
                        <td>${{ number_format($producto->precio_venta_producto, 2) }}</td>
                        <td>{{ $producto->compras_id }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <a class="btn btn-primary btn-sm" href="{{ route('productos.show', $producto->id) }}">Ver</a>
                                <a class="btn btn-success btn-sm" href="{{ route('productos.edit', $producto->id) }}">Editar</a>
                                <form action="{{ route('productos.destroy', $producto->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este producto?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-slate-500">No hay productos para mostrar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $productos->links('vendor.pagination.admin-clean') }}
    </div>
</div>
