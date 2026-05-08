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

                                        <th>Id</th>

                                        <th>Descripcion</th>

                                        <th>Total Venta</th>

                                        <th>Total Pedido</th>

                                        <th>Ganancia</th>

                                        <th>Fecha</th>

                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sum_total_ped=0; ?>
                                    <?php $sum_total=0; ?>
                                    <?php $sum_total_gan=0; ?>
                                    @foreach ($repo as $reportes)
                                        <tr>

                                            <td>{{ $reportes->pedido_id }}</td>

                                            <td>{{ $reportes->descripcion }}</td>

                                            <td>{{ $reportes->total_pedido }}</td>

                                            <td>{{ $reportes->total }}</td>

                                            <td>{{ $reportes->ganancia }}</td>

                                            <td>{{ $reportes->created_at }}</td>

                                            <td><a class="btn btn-sm btn-primary " href="{{ route('show',$reportes->pedido_id) }}"><i class="fa fa-fw fa-eye"></i> {{ __('Show') }}</a></td>

                                        </tr>

                                        <?php   $sum_total_ped=$sum_total_ped + $reportes->total_pedido?>
                                        <?php   $sum_total=$sum_total + $reportes->total?>
                                        <?php   $sum_total_gan=$sum_total_gan + $reportes->ganancia?>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th scope="row">Sumatorias: </th>
                                        <td></td>
                                        <td>{{$sum_total_ped}}</td>
                                        <td>{{$sum_total}}</td>
                                        <td>{{$sum_total_gan}}</td>
                                        <td></td>

                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
              
            </div>
        </div>
    </div>
@endsection
