@extends('layouts.app')

@section('template_title')
    {{ $producto->name ?? "{{ __('Show') Producto" }}
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">{{ __('Show') }} Producto</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('productos.index') }}"> {{ __('Back') }}</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <div class="form-group">
                            <strong>Codigo Producto:</strong>
                            {{ $producto->codigo_producto }}
                        </div>
                        <div class="form-group">
                            <strong>Descripcion Producto:</strong>
                            {{ $producto->descripcion_producto }}
                        </div>
                        <div class="form-group">
                            <strong>Cantidad Compra Producto:</strong>
                            {{ $producto->cantidad_compra_producto }}
                        </div>
                        <div class="form-group">
                            <strong>Stock Venta Producto:</strong>
                            {{ $producto->stock_venta_producto }}
                        </div>
                        <div class="form-group">
                            <strong>Precio Pesos Producto:</strong>
                            {{ $producto->precio_pesos_producto }}
                        </div>
                        <div class="form-group">
                            <strong>Precio Dolares Producto:</strong>
                            {{ $producto->precio_dolares_producto }}
                        </div>
                        <div class="form-group">
                            <strong>Precio Venta Producto:</strong>
                            {{ $producto->precio_venta_producto }}
                        </div>
                        <div class="form-group">
                            <strong>Compras Id:</strong>
                            {{ $producto->compras_id }}
                        </div>

                        <div class="form-group">
    <strong>Imagen del Producto:</strong><br>
    @if($producto->imagen_producto)
        <img src="{{ asset('storage/' . $producto->imagen_producto) }}"
             alt="Imagen Producto"
             style="max-width:800px; height:auto; border-radius:5px; border:1px solid #ccc;">
    @else
        <span class="text-muted">No hay imagen cargada para este producto.</span>
    @endif
</div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
