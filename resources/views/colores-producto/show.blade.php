@extends('layouts.app')

@section('template_title')
    {{ $coloresProducto->name ?? "{{ __('Show') Colores Producto" }}
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">{{ __('Show') }} Colores Producto</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('colores-productos.index') }}"> {{ __('Back') }}</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <div class="form-group">
                            <strong>Producto Id:</strong>
                            {{ $coloresProducto->producto_id }}
                        </div>
                        <div class="form-group">
                            <strong>Colores Id:</strong>
                            {{ $coloresProducto->colores_id }}
                        </div>
                        <div class="form-group">
                            <strong>Cantidad Por Color:</strong>
                            {{ $coloresProducto->cantidad_por_color }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
