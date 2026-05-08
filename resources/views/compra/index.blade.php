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
                                {{ __('Compra') }}
                            </span>

                             <div class="float-right">
                                <a href="{{ route('compras.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                  {{ __('Create New') }}
                                </a>
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
                                        
										<th>Codigo Compra</th>
										<th>Descripcion Compra</th>
										<th>Costo Env Compra</th>
                                        <th>Costo Imp Compra $</th>
										<th>Total Compra Pesos</th>
                                        <th>Total Compra Dolares</th>
                                        <th>Total Final Compra</th>
                                        <th>Total Ventas</th>
                                        <th>Ganancia x Caja</th>
										<th>Proveedor Id</th>

                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($compras as $compra)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            
											<td>{{ $compra->codigo_compra }}</td>
											<td>{{ $compra->descripcion_compra }}</td>
											<td>{{ $compra->envio_compra }}</td>
                                            <td>{{ $compra->importacion_compra }}</td>
											<td>{{ $compra->total_pesos_compra }}</td>
                                            <td>{{ $compra->total_dolares_compra }}</td>
                                            <td>{{ $compra->total_final_compra }}</td>
                                            <td><strong>{{ $compra->total_ventas }}</strong></td>
                                            <td><strong>{{ $compra->total_ventas - $compra->total_final_compra}}</strong></td>
											<td>{{ $compra->proveedor_id }}</td>

                                            <td>
                                                <form action="{{ route('compras.destroy',$compra->id) }}" method="POST">
                                                    <a class="btn btn-sm btn-primary " href="{{ route('compras.show',$compra->id) }}"><i class="fa fa-fw fa-eye"></i></a>
                                                    <a class="btn btn-sm btn-success" href="{{ route('compras.edit',$compra->id) }}"><i class="fa fa-fw fa-edit"></i></a>
                                                    <a class="btn btn-sm btn-warning" href="{{ route('productos.create',['id' => $compra->id]) }}"></i> {{ __('agregar producto') }}</a>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-fw fa-trash"></i></button>
                                                </form>
                                                @if(($compra->estado_compra ?? '1') !== 'ANULADA')
                                                    <form action="{{ route('compras.anular', $compra->id) }}" method="POST" class="mt-1">
                                                        @csrf
                                                        <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Esto revertirá entradas de inventario de la compra. ¿Continuar?')">
                                                            Anular compra
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                {!! $compras->links() !!}
            </div>
        </div>
    </div>
@endsection
