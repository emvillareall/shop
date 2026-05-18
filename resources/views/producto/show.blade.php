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
                            <strong>Imagen principal:</strong><br>
                            <img src="{{ $producto->imageUrl() }}"
                                 alt="Imagen Producto"
                                 style="max-width:340px; width:100%; height:auto; border-radius:8px; border:1px solid #cbd5e1; object-fit:cover;">
                        </div>

                        <div class="form-group mt-3">
                            <strong>Galeria del producto:</strong>
                            @php
                                $gallery = $producto->allImageUrls();
                            @endphp
                            @if(count($gallery))
                                <div class="d-flex flex-wrap mt-2" style="gap:10px;">
                                    @foreach($gallery as $url)
                                        <a href="{{ $url }}" target="_blank" rel="noopener">
                                            <img src="{{ $url }}"
                                                 alt="Imagen galeria {{ $loop->iteration }}"
                                                 style="width:90px;height:90px;border-radius:8px;border:1px solid #cbd5e1;object-fit:cover;">
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mt-2">No hay imagenes cargadas para este producto.</p>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
