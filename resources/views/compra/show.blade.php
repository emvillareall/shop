@extends('layouts.app')

@section('template_title')
    Compra #{{ $compra->id }}
@endsection

@section('content')
    @php
        $estadoCompra = strtoupper((string) ($compra->estado_compra ?? ''));
        $estadoRecepcion = $estadoCompra === 'ANULADA'
            ? 'ANULADA'
            : ($compra->estado_recepcion === null ? 'HISTÓRICA / LEGACY' : (string) $compra->estado_recepcion);
        $estadoRecepcionLabel = [
            'EN_TRANSITO' => 'EN TRÁNSITO',
            'RECEPCION_PARCIAL' => 'RECEPCIÓN PARCIAL',
            'RECIBIDA' => 'RECIBIDA',
            'CON_INCIDENCIAS' => 'CON INCIDENCIAS',
            'ANULADA' => 'ANULADA',
            'HISTÓRICA / LEGACY' => 'HISTÓRICA / LEGACY',
        ][$estadoRecepcion] ?? $estadoRecepcion;
        $estadoRecepcionClass = in_array($estadoRecepcion, ['RECIBIDA'], true)
            ? 'bg-emerald-100 text-emerald-800'
            : (in_array($estadoRecepcion, ['CON_INCIDENCIAS', 'ANULADA'], true)
                ? 'bg-rose-100 text-rose-800'
                : (in_array($estadoRecepcion, ['RECEPCION_PARCIAL'], true)
                    ? 'bg-amber-100 text-amber-800'
                    : 'bg-slate-100 text-slate-700'));
        $gestionEstadoLabels = [
            'BORRADOR' => 'Borrador',
            'ENVIADO' => 'Enviado',
            'EN_GESTION' => 'En gestión',
            'RESUELTO' => 'Resuelto',
            'CANCELADO' => 'Cancelado',
        ];
    @endphp

    <div class="mb-5 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="mb-2 flex flex-wrap items-center gap-3">
                <span class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Compras</span>
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $estadoRecepcionClass }}">
                    {{ $estadoRecepcionLabel }}
                </span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Compra #{{ $compra->id }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $compra->codigo_compra }} · {{ $compra->descripcion_compra }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($recepcionHabilitada)
                <a href="{{ route('compras.recepcion.create', $compra) }}" class="btn btn-primary">
                    <i class="fa-solid fa-box-open mr-1"></i> Recibir mercadería
                </a>
            @endif
            <a href="{{ route('compras.index') }}" class="btn btn-secondary">Volver a compras</a>
        </div>
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Proveedor</p>
            <p class="mt-1 font-bold text-slate-900">{{ $compra->proveedor?->tienda_proveedor ?? 'No especificado' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de compra</p>
            <p class="mt-1 font-bold text-slate-900">{{ optional($compra->created_at)->format('d/m/Y H:i') ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total compra</p>
            <p class="mt-1 font-bold text-slate-900">${{ number_format((float) ($compra->total_final_compra ?? 0), 2) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado de recepción</p>
            <p class="mt-1 font-bold text-slate-900">{{ $estadoRecepcionLabel }}</p>
        </div>
    </div>

    <details class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm md:p-5">
        <summary class="cursor-pointer">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700">Gestión con proveedor</p>
                <h2 class="mt-1 text-xl font-extrabold text-slate-900">Reclamos de esta compra</h2>
                <p class="mt-1 text-sm text-slate-600">
                    La recepción original permanece separada e inmutable. Las reposiciones solo ingresan al stock cuando se reciben y aceptan físicamente.
                </p>
            </div>
        </div>
        </summary>

        @if($gestionProveedor['incidencias_reclamables']->isNotEmpty())
            <div class="mt-4 rounded-xl border border-amber-200 bg-white p-4">
                <h3 class="font-bold text-slate-900">Crear reclamo desde una incidencia</h3>
                <p class="mt-1 text-sm text-slate-500">Selecciona el tipo y la cantidad. Las unidades aceptadas no aparecen como reclamables.</p>
                <div class="mt-3 space-y-3">
                    @foreach($gestionProveedor['incidencias_reclamables'] as $incidencia)
                        <form method="POST" action="{{ route('compras.reclamos.store', $compra) }}" class="rounded-lg border border-slate-200 p-3">
                            @csrf
                            <div class="grid gap-3 md:grid-cols-12 md:items-end">
                                <div class="md:col-span-4">
                                    <p class="font-semibold text-slate-800">{{ $incidencia['producto'] }}</p>
                                    <p class="text-sm text-slate-500">{{ $incidencia['color'] }} · Talla {{ $incidencia['talla'] }}</p>
                                    <input type="hidden" name="lineas[0][compra_recepcion_item_id]" value="{{ $incidencia['compra_recepcion_item_id'] }}">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="form-label">Tipo de incidencia</label>
                                    <select name="lineas[0][tipo_incidencia]" class="form-control" required>
                                        @foreach($incidencia['tipos_disponibles'] as $tipo)
                                            <option value="{{ $tipo['tipo_incidencia'] }}">{{ $tipo['tipo_incidencia'] }} · saldo {{ $tipo['cantidad_reclamable'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="form-label">Cantidad</label>
                                    <input type="number" name="lineas[0][cantidad_reclamada]" class="form-control" min="1" required>
                                </div>
                                <div class="md:col-span-3">
                                    <label class="form-label">Observación</label>
                                    <input type="text" name="lineas[0][observacion]" class="form-control" maxlength="1000">
                                </div>
                            </div>
                            <div class="mt-3 flex justify-end">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fa-solid fa-file-circle-plus mr-1"></i> Crear reclamo
                                </button>
                            </div>
                        </form>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                Esta compra no tiene incidencias pendientes de reclamo.
            </div>
        @endif

        @if($gestionProveedor['reclamos']->isNotEmpty())
            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr><th>Reclamo</th><th>Proveedor</th><th>Estado</th><th>Líneas</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($gestionProveedor['reclamos'] as $resumenReclamo)
                            <tr>
                                <td>#{{ $resumenReclamo['modelo']->id }}</td>
                                <td>{{ $compra->proveedor?->tienda_proveedor ?? '—' }}</td>
                                <td>{{ $gestionEstadoLabels[$resumenReclamo['modelo']->estado] ?? $resumenReclamo['modelo']->estado }}</td>
                                <td>{{ $resumenReclamo['lineas']->count() }}</td>
                                <td class="text-right"><a class="btn btn-sm btn-outline-primary" href="{{ route('compras.reclamos.show', [$compra, $resumenReclamo['modelo']]) }}">Ver reclamo</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </details>

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('Producto') }}
                            </span>

                        </div>
                    </div>
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success">
                            <p>{{ $message }}</p>
                        </div>
                    @endif

                    <div class="card-body">
                        @if ($message = Session::get('danger'))
                            <div class="alert alert-danger">
                                <p>{{ $message }}</p>
                            </div>
                        @endif

                        <details class="card mb-3">
                            <summary class="card-header">
                                Ajuste controlado de inventario (kardex)
                            </summary>
                            <div class="card-body">
                                <form method="POST" action="{{ route('compras.ajustar_inventario', $id) }}">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <label class="form-label">Variante</label>
                                            <select id="variante_selector" class="form-control" required>
                                                <option value="">Selecciona una variante</option>
                                                @foreach($variantes as $v)
                                                    <option
                                                        value="{{ $v->producto_id }}|{{ $v->colores_id }}|{{ $v->talla_por_color }}"
                                                        data-producto="{{ $v->producto_id }}"
                                                        data-color="{{ $v->colores_id }}"
                                                        data-talla="{{ $v->talla_por_color }}"
                                                    >
                                                        {{ $v->descripcion_producto }} | {{ $v->nombre_color ?? ('Color #' . $v->colores_id) }} | Talla {{ $v->talla_por_color }} | Stock {{ $v->stock_por_color }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Producto ID</label>
                                            <input type="number" name="producto_id" id="producto_id" class="form-control" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Color ID</label>
                                            <input type="number" name="colores_id" id="colores_id" class="form-control" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Talla</label>
                                            <input type="text" name="talla_por_color" id="talla_por_color" class="form-control" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Cantidad ajuste (+/-)</label>
                                            <input type="number" name="cantidad_ajuste" class="form-control" required>
                                        </div>
                                        <div class="col-md-9">
                                            <label class="form-label">Motivo</label>
                                            <input type="text" name="motivo" class="form-control" placeholder="Ajuste por conteo fisico, correccion, etc.">
                                        </div>
                                        <div class="col-md-3 d-grid align-items-end">
                                            <button type="submit" class="btn btn-warning mt-4">Aplicar ajuste</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </details>

                        <details class="card mb-3">
                            <summary class="card-header">
                                Items formales de compra
                            </summary>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Producto</th>
                                                <th>Color</th>
                                                <th>Talla</th>
                                                <th>Cantidad</th>
                                                <th>Recibido físicamente</th>
                                                <th>Aceptado acum.</th>
                                                <th>Pendiente</th>
                                                <th>Stock ingresado</th>
                                                <th>Costo U $</th>
                                                <th>Subtotal $</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($compraItems as $item)
                                                <tr>
                                                    <td>{{ $item->id }}</td>
                                                    <td>{{ $item->descripcion_producto ?? ('Producto #' . $item->producto_id) }}</td>
                                                    <td>{{ $item->nombre_color ?? ($item->colores_id ? ('Color #' . $item->colores_id) : '-') }}</td>
                                                    <td>{{ $item->talla ?: '-' }}</td>
                                                    <td>{{ $item->cantidad }}</td>
                                                    <td>{{ $item->fisicamente_contabilizada }}</td>
                                                    <td>{{ $item->aceptada_acumulada }}</td>
                                                    <td class="font-weight-bold {{ $item->pendiente_recepcion > 0 ? 'text-warning' : 'text-success' }}">{{ $item->pendiente_recepcion }}</td>
                                                    <td>{{ $item->stock_ingresado }}</td>
                                                    <td>{{ number_format((float) $item->costo_unitario_dolares, 2) }}</td>
                                                    <td>{{ number_format((float) $item->subtotal_dolares, 2) }}</td>
                                                    <td>{{ $item->estado }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="12" class="text-center text-muted">Sin items formales registrados para esta compra.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </details>

                        <details class="card mb-3">
                            <summary class="card-header">
                                Trazabilidad operativa (compra_item -> movimientos -> stock final)
                            </summary>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Variante</th>
                                                <th>Stock ingresado</th>
                                                <th>Saldo movimientos</th>
                                                <th>Stock final variante</th>
                                                <th>Movimientos</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($trazabilidad as $t)
                                                <tr>
                                                    <td>#{{ $t->id }}</td>
                                                    <td>
                                                        Prod {{ $t->producto_id }} / Color {{ $t->colores_id }} / Talla {{ $t->talla }}
                                                    </td>
                                                    <td>{{ $t->stock_ingresado }}</td>
                                                    <td>{{ $t->saldo_movimientos }}</td>
                                                    <td>{{ $t->stock_final ?? '-' }}</td>
                                                    <td>
                                                        @if($t->movimientos->isEmpty())
                                                            <span class="text-muted">Sin movimientos</span>
                                                        @else
                                                            <ul class="mb-0 ps-3">
                                                                @foreach($t->movimientos as $m)
                                                                    <li>
                                                                        [{{ optional($m->created_at)->format('Y-m-d H:i') }}]
                                                                        {{ $m->tipo_movimiento }}:
                                                                        {{ $m->cantidad }}
                                                                        ({{ $m->stock_antes }} -> {{ $m->stock_despues }})
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">Sin trazabilidad disponible.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </details>

                        <details class="card mb-3">
                            <summary class="card-header d-flex flex-wrap justify-content-between align-items-center">
                                <span>Historial de recepciones</span>
                                @if($recepcionHabilitada)
                                    <a href="{{ route('compras.recepcion.create', $compra) }}" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-plus mr-1"></i> Nueva recepción
                                    </a>
                                @endif
                            </summary>
                            <div class="card-body">
                                @forelse($recepciones as $recepcion)
                                    <div class="mb-4 rounded-lg border border-slate-200 p-3 last:mb-0">
                                        <div class="mb-3 flex flex-col gap-1 text-sm sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <strong>{{ optional($recepcion->fecha_recepcion)->format('d/m/Y H:i') }}</strong>
                                                <span class="text-muted">
                                                    · {{ $recepcion->user?->name ?? 'Usuario no identificado' }}
                                                </span>
                                            </div>
                                            @if($recepcion->observacion)
                                                <span class="text-muted">{{ $recepcion->observacion }}</span>
                                            @endif
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Variante</th>
                                                        <th>Recibidas</th>
                                                        <th>Aceptadas</th>
                                                        <th>Dañadas</th>
                                                        <th>Incorrectas</th>
                                                        <th>Faltantes</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recepcion->items as $linea)
                                                        <tr>
                                                            <td>
                                                                {{ $linea->compraItem?->producto?->descripcion_producto ?? ('Producto #' . ($linea->compraItem?->producto_id ?? '—')) }}
                                                                · {{ $linea->compraItem?->color?->nombre_color ?? ('Color #' . ($linea->compraItem?->colores_id ?? '—')) }}
                                                                · Talla {{ $linea->compraItem?->talla ?? '—' }}
                                                            </td>
                                                            <td>{{ $linea->cantidad_recibida }}</td>
                                                            <td>{{ $linea->cantidad_aceptada }}</td>
                                                            <td>{{ $linea->cantidad_danada }}</td>
                                                            <td>{{ $linea->cantidad_incorrecta }}</td>
                                                            <td>{{ $linea->cantidad_faltante_declarada }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @empty
                                    <p class="mb-0 text-muted">Todavía no hay recepciones registradas.</p>
                                @endforelse
                            </div>
                        </details>

                        <details class="card mb-3">
                            <summary class="card-header">Resumen/listado de productos</summary>
                            <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead">
                                    <tr>
                                        
                                        <th>Codigo Producto</th>
                                        <th>Descripcion Producto</th>
                                        <th>Cantidad Compra Producto</th>
                                        <th>Stock Venta Producto</th>
                                        <th>Precio Pesos Producto</th>
                                        <th>Precio Dolares Producto</th>
                                        <th>Precio Total Producto Pesos.</th>
                                        <th>Precio Total Producto Dolar.</th>
                                        <th>Precio Venta Producto</th>
                                        <th>Compras Id</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $total_dolares=0; ?>
                                    <?php $total_pesos=0; ?>
                                    @foreach ($productos as $producto)
                                        <tr>
                                            
                                            <td>{{ $producto->codigo_producto }}</td>
                                            <td>{{ $producto->descripcion_producto }}</td>
                                            <td>{{ $producto->cantidad_compra_producto }}</td>
                                            <td>{{ $producto->stock_venta_producto }}</td>
                                            <td>{{ $producto->precio_pesos_producto }}</td>
                                            <td>{{ $producto->precio_dolares_producto }}</td>
                                            <td>{{ $producto->precio_pesos_producto * $producto->cantidad_compra_producto }}</td>
                                            <td>{{ $producto->precio_dolares_producto * $producto->cantidad_compra_producto }}</td>
                                            <td>{{ $producto->precio_venta_producto }}</td>
                                            <td>{{ $producto->compras_id }}</td>

                                        </tr>
                                        <?php   $total_dolares=$total_dolares+$producto->precio_dolares_producto * $producto->cantidad_compra_producto ?>
                                        <?php   $total_pesos=$total_pesos+$producto->precio_pesos_producto * $producto->cantidad_compra_producto ?>

                                    @endforeach
                                </tbody>
                                                                <tfoot>
                                    <tr>
                                        <th scope="row">Sumatorias: </th>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>{{$total_pesos}}</td>
                                        <td>{{$total_dolares}}</td>
                                        <td></td>
                                        <td></td>

                                    </tr>

                                </tfoot>
                            </table>
                        </div>
                            </div>
                        </details>
                    </div>
                </div>
                {!! $productos->links() !!}
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selector = document.getElementById('variante_selector');
            if (!selector) return;

            selector.addEventListener('change', function () {
                const opt = selector.options[selector.selectedIndex];
                if (!opt || !opt.dataset.producto) return;
                document.getElementById('producto_id').value = opt.dataset.producto;
                document.getElementById('colores_id').value = opt.dataset.color;
                document.getElementById('talla_por_color').value = opt.dataset.talla;
            });
        });
    </script>
@endsection
