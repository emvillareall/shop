<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <input type="text"
               wire:model.live.debounce.300ms="search"
               placeholder="Buscar por código o descripción..."
               class="w-full max-w-sm rounded-md border border-slate-300 px-3 py-2 text-sm">
        <a href="{{ route('compras.index') }}" class="btn btn-secondary btn-sm">Volver a Compras</a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>Imagen</th>
                <th>Código</th>
                <th>Descripción</th>
                <th>Stock por Color/Talla</th>
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
                        @if($producto->imagen_producto)
                            <img src="{{ asset('storage/'.$producto->imagen_producto) }}" alt="{{ $producto->codigo_producto }}" class="h-14 w-14 rounded-md object-cover">
                        @else
                            <div class="flex h-14 w-14 items-center justify-center rounded-md bg-slate-100 text-xs text-slate-400">N/A</div>
                        @endif
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
                            <a class="btn btn-primary btn-sm" href="{{ route('productos.show',$producto->id) }}">Ver</a>
                            <a class="btn btn-success btn-sm" href="{{ route('productos.edit',$producto->id) }}">Editar</a>
                            <form action="{{ route('productos.destroy',$producto->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este producto?')">
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
        {{ $productos->links() }}
    </div>
</div>
