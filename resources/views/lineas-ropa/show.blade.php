@extends('layouts.app')

@section('template_title')
    {{ $lineasRopa->name ?? __('Show') . " " . __('Lineas Ropa') }}
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="float-left">
                            <span class="card-title">{{ __('Show') }} Lineas Ropa</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary btn-sm" href="{{ route('lineas-ropa.index') }}"> {{ __('Back') }}</a>
                        </div>
                    </div>

                    <div class="card-body bg-white">
                        
                        <div class="form-group mb-2 mb20">
                            <strong>Nombre Linea:</strong>
                            {{ $lineasRopa->nombre_linea }}
                        </div>
                        <div class="form-group mb-2 mb20">
                            <strong>Estado Linea:</strong>
                            {{ $lineasRopa->estado_linea }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
