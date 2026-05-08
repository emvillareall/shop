@extends('layouts.app')

@section('template_title')
    {{ $proveedore->name ?? "{{ __('Show') Proveedore" }}
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">{{ __('Show') }} Proveedore</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('proveedores.index') }}"> {{ __('Back') }}</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <div class="form-group">
                            <strong>Tienda Proveedor:</strong>
                            {{ $proveedore->tienda_proveedor }}
                        </div>
                        <div class="form-group">
                            <strong>Nombres Proveedor:</strong>
                            {{ $proveedore->nombres_proveedor }}
                        </div>
                        <div class="form-group">
                            <strong>Apellidos Proveedor:</strong>
                            {{ $proveedore->apellidos_proveedor }}
                        </div>
                        <div class="form-group">
                            <strong>Cedula Proveedor:</strong>
                            {{ $proveedore->cedula_proveedor }}
                        </div>
                        <div class="form-group">
                            <strong>Telefono Proveedor:</strong>
                            {{ $proveedore->telefono_proveedor }}
                        </div>
                        <div class="form-group">
                            <strong>Direccion Proveedor:</strong>
                            {{ $proveedore->direccion_proveedor }}
                        </div>
                        <div class="form-group">
                            <strong>Email Proveedor:</strong>
                            {{ $proveedore->email_proveedor }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
