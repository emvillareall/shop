@extends('layouts.app')

@section('template_title')
    Compra
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('REPORTES') }}
                            </span>

                             <div class="float-right">
                              </div>
                        </div>
                    </div>
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success">
                            <p>{{ $message }}</p>
                        </div>
                    @endif

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead">
                                    <tr>
                                        <th>No</th>
                                        <th>Descripcion</th>
                                        <th>Cantidad</th>
                                        <th>precio_compra_real_producto</th>
                                        <th>precio_venta_producto</th>
                                        <th>descuento_pedido</th>
                                        <th>subtotal_compra</th>
                                        <th>subtotal_venta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <label>Nombre Cliente: </label> {{$clientes->nombres_clientes}} {{$clientes->apellidos_clientes}}<br>
                                    <label>Pedido_id: </label> {{$pedidos->id}}<br>
                                    <label>Descripcion_pedido: </label> {{$pedidos->descripcion}}
                                    
                                    <?php $total=0; ?>
                                    @foreach ($reportes as $reporte)
                                        <tr>
                                             <td>{{ ++$i }}</td>
                                             <td>{{ $reporte->descripcion_producto}}</td>
                                             <td>{{ $reporte->cantidad_producto}}</td>
                                             <td>{{ $reporte->precio_dolares_producto}}</td>
                                             <td>{{ $reporte->precio_venta_producto}}</td>
                                             <td>{{ $reporte->descuentos_pedido}}</td>
                                             <td>{{ $reporte->cantidad_producto * $reporte->precio_dolares_producto}}</td>
                                             <td>{{ $reporte->cantidad_producto * $reporte->precio_venta_producto}}</td>
                                        </tr>
                                        <?php   $total=$total+$reporte->cantidad_producto * $reporte->precio_dolares_producto ?>
                                        <?php  $total_final=$reporte->total_pedido ?>
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
                                        <td>{{$total}}</td>
                                        <td>{{$total_final}}</td>
                                        <td></td>
                                        <td></td>

                                    </tr>
                                    <tr>
                                        <th scope="row">Ganancia Neta: </th>
                                        <td>{{$total_final - $total}}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                {!! $reportes->links() !!}
            </div>
        </div>
    </div>
@endsection
