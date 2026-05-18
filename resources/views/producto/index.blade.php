@extends('layouts.app')

@section('template_title')
    Productos
@endsection

@section('content')
    <div class="space-y-4">
        <div class="card">
            <div class="card-header">
                <h1 class="text-base font-semibold">Listado de Productos</h1>
            </div>
            <div class="card-body">
                <style>
                    .productos-filtros-grid {
                        display: grid;
                        grid-template-columns: 2.2fr 1.4fr 1.4fr 1.2fr .9fr .9fr auto;
                        gap: 8px;
                        align-items: center;
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
                        .productos-filtros-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
                    }
                    @media (max-width: 768px) {
                        .productos-filtros-grid { grid-template-columns: 1fr; }
                    }
                </style>

                <form method="GET" action="{{ route('productos.index') }}" class="rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
                    <div class="productos-filtros-grid">
                        <input type="text" name="search"
                               value="{{ $filters['search'] ?? '' }}"
                               placeholder="Buscar por codigo o descripcion..."
                               class="productos-filtro-control rounded-md border border-slate-300">

                        <select id="filtro-linea" name="linea" class="productos-filtro-control rounded-md border border-slate-300">
                            <option value="">Todas las lineas</option>
                            @foreach($lineas as $lineaOpt)
                                <option value="{{ $lineaOpt->id }}" @selected(($filters['linea'] ?? '') == (string) $lineaOpt->id)>
                                    {{ $lineaOpt->nombre_linea }}
                                </option>
                            @endforeach
                        </select>

                        <select id="filtro-categoria" name="categoria" class="productos-filtro-control rounded-md border border-slate-300">
                            <option value="">Todas las categorias</option>
                            @foreach($categorias as $categoriaOpt)
                                <option value="{{ $categoriaOpt->id }}"
                                        data-linea="{{ $categoriaOpt->linea_ropa_id }}"
                                        @selected(($filters['categoria'] ?? '') == (string) $categoriaOpt->id)>
                                    {{ $categoriaOpt->nombre_categoria }}
                                </option>
                            @endforeach
                        </select>

                        <select name="estado_stock" class="productos-filtro-control rounded-md border border-slate-300">
                            <option value="">Stock: todos</option>
                            <option value="disponible" @selected(($filters['estado_stock'] ?? '') === 'disponible')>Disponible</option>
                            <option value="poco" @selected(($filters['estado_stock'] ?? '') === 'poco')>Pocas unidades</option>
                            <option value="agotado" @selected(($filters['estado_stock'] ?? '') === 'agotado')>Agotado</option>
                        </select>

                        <input type="number" step="0.01" min="0" name="precio_min"
                               value="{{ $filters['precio_min'] ?? '' }}"
                               placeholder="Precio min."
                               class="productos-filtro-control rounded-md border border-slate-300">

                        <input type="number" step="0.01" min="0" name="precio_max"
                               value="{{ $filters['precio_max'] ?? '' }}"
                               placeholder="Precio max."
                               class="productos-filtro-control rounded-md border border-slate-300">

                        <div class="flex items-center gap-2">
                            <button type="submit" class="btn btn-primary btn-sm productos-filtro-btn">Aplicar</button>
                            <a href="{{ route('productos.index') }}" class="btn btn-light btn-sm productos-filtro-btn">Limpiar</a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive mt-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Imagen</th>
                                <th>Codigo</th>
                                <th>Descripcion</th>
                                <th>Stock por color/talla</th>
                                <th>Precio (P)</th>
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
                                            <form action="{{ route('productos.destroy', $producto->id) }}" method="POST" onsubmit="return confirm('Seguro que deseas eliminar este producto?')">
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

                <div class="mt-2">
                    {{ $productos->links('vendor.pagination.admin-clean') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lineaSelect = document.getElementById('filtro-linea');
    const categoriaSelect = document.getElementById('filtro-categoria');
    if (!lineaSelect || !categoriaSelect) return;

    const allOptions = Array.from(categoriaSelect.querySelectorAll('option'));
    const defaultOption = allOptions.find(o => o.value === '');

    function rebuildCategorias() {
        const lineaId = lineaSelect.value;
        const currentValue = categoriaSelect.value;

        const visibles = allOptions.filter(opt => {
            if (opt.value === '') return true;
            if (!lineaId) return true;
            return String(opt.dataset.linea || '') === String(lineaId);
        });

        categoriaSelect.innerHTML = '';
        visibles.forEach(opt => categoriaSelect.appendChild(opt));

        if (visibles.some(opt => opt.value === currentValue)) {
            categoriaSelect.value = currentValue;
        } else if (defaultOption) {
            categoriaSelect.value = '';
        }
    }

    lineaSelect.addEventListener('change', rebuildCategorias);
    rebuildCategorias();
});
</script>
@endsection
