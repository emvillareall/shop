@extends('layouts.app')

@section('template_title')
    {{ __('Show') }} Colores
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="mx-auto w-full max-w-4xl">
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="float-left">
                        <span class="card-title">Detalle de color</span>
                    </div>
                    <div class="float-right">
                        <a class="btn btn-primary btn-sm" href="{{ route('colores.index') }}">Volver</a>
                    </div>
                </div>

                <div class="card-body bg-white">
                    <div class="form-group mb-3">
                        <strong>Nombre:</strong>
                        {{ $colore->nombre_color }}
                    </div>
                    <div class="form-group mb-2">
                        <strong>Codigo:</strong>
                        <code>{{ $colore->codigo_color }}</code>
                    </div>
                    <div class="form-group mb-0">
                        <strong>Muestra:</strong>
                        <span class="ml-2 inline-block h-6 w-6 rounded-full border border-slate-300 align-middle"
                              style="background-color: {{ $colore->codigo_color }}"></span>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
