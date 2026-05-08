@extends('layouts.app')

@section('template_title')
    {{ $categoriasProducto->name ?? __('Show') . " " . __('Categorias Producto') }}
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="float-left">
                            <span class="card-title">{{ __('Show') }} Categorias Producto</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary btn-sm" href="{{ route('categorias-productos.index') }}"> {{ __('Back') }}</a>
                        </div>
                    </div>

                    <div class="card-body bg-white">
                        
                        <div class="form-group mb-2 mb20">
                            <strong>Nombre Categoria:</strong>
                            {{ $categoriasProducto->nombre_categoria }}
                        </div>
                        <div class="form-group mb-2 mb20">
                            <strong>Linea Ropa Id:</strong>
                            {{ $categoriasProducto->linea_ropa_id }}
                        </div>
                        <div class="form-group mb-2 mb20">
                            <strong>Estado Categoria:</strong>
                            {{ $categoriasProducto->estado_categoria }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
