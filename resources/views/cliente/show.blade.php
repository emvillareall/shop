@extends('layouts.app')

@section('template_title')
    {{ $cliente->name ?? "{{ __('Show') Cliente" }}
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">{{ __('Show') }} Cliente</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('clientes.index') }}"> {{ __('Back') }}</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <div class="form-group">
                            <strong>Nombres Clientes:</strong>
                            {{ $cliente->nombres_clientes }}
                        </div>
                        <div class="form-group">
                            <strong>Apellidos Clientes:</strong>
                            {{ $cliente->apellidos_clientes }}
                        </div>
                        <div class="form-group">
                            <strong>Cedula Clientes:</strong>
                            {{ $cliente->cedula_clientes }}
                        </div>
                        <div class="form-group">
                            <strong>Telefono Clientes:</strong>
                            {{ $cliente->telefono_clientes }}
                        </div>
                        <div class="form-group">
                            <strong>Ciudad Clientes:</strong>
                            {{ $cliente->ciudad_clientes }}
                        </div>
                        <div class="form-group">
                            <strong>Direccion Clientes:</strong>
                            {{ $cliente->direccion_clientes }}
                        </div>
                        <div class="form-group">
                            <strong>Email Dueno Clientes:</strong>
                            {{ $cliente->email_dueno_clientes }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
