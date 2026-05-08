@extends('layouts.app')

@section('template_title')
    {{ __('Create') }} Lineas Ropa
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="mx-auto w-full max-w-5xl">
            <div class="card card-default">
                <div class="card-header">
                    <span class="card-title">Crear linea de ropa</span>
                </div>
                <div class="card-body bg-white">
                    <form method="POST" action="{{ route('lineas-ropa.store') }}" role="form" enctype="multipart/form-data">
                        @csrf
                        @include('lineas-ropa.form')
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
