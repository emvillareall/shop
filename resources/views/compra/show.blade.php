@extends('layouts.app')

@section('template_title')
    Producto
@endsection

@section('content')
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

                        <div class="card mb-3">
                            <div class="card-header">
                                Ajuste controlado de inventario (kardex)
                            </div>
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
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                Items formales de compra
                            </div>
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
                                                    <td>{{ $item->stock_ingresado }}</td>
                                                    <td>{{ number_format((float) $item->costo_unitario_dolares, 2) }}</td>
                                                    <td>{{ number_format((float) $item->subtotal_dolares, 2) }}</td>
                                                    <td>{{ $item->estado }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted">Sin items formales registrados para esta compra.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                Trazabilidad operativa (compra_item -> movimientos -> stock final)
                            </div>
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
                        </div>

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
