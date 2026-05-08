@extends('layouts.app')

@section('template_title')
    {{ $tienda->name ?? "{{ __('Show') Tienda" }}
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">{{ __('Show') }} Tienda</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('tiendas.index') }}"> {{ __('Back') }}</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <div class="form-group">
                            <strong>Nombre Tienda:</strong>
                            {{ $tienda->nombre_tienda }}
                        </div>
                        <div class="form-group">
                            <strong>Nombres Dueno Tienda:</strong>
                            {{ $tienda->nombres_dueno_tienda }}
                        </div>
                        <div class="form-group">
                            <strong>Apellidos Dueno Tienda:</strong>
                            {{ $tienda->apellidos_dueno_tienda }}
                        </div>
                        <div class="form-group">
                            <strong>Cedula Dueno Tienda:</strong>
                            {{ $tienda->cedula_dueno_tienda }}
                        </div>
                        <div class="form-group">
                            <strong>Telefono Dueno Tienda:</strong>
                            {{ $tienda->telefono_dueno_tienda }}
                        </div>
                        <div class="form-group">
                            <strong>Direccion Dueno Tienda:</strong>
                            {{ $tienda->direccion_dueno_tienda }}
                        </div>
                        <div class="form-group">
                            <strong>Email Dueno Tienda:</strong>
                            {{ $tienda->email_dueno_tienda }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
