@extends('layouts.app')

@section('template_title')
    {{ __('Create') }} Categorias Producto
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="mx-auto w-full max-w-5xl">
            <div class="card card-default">
                <div class="card-header">
                    <span class="card-title">Crear categoría de producto</span>
                </div>
                <div class="card-body bg-white">
                    <form method="POST" action="{{ route('categorias-productos.store') }}" role="form" enctype="multipart/form-data">
                        @csrf
                        @include('categorias-producto.form')
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
