@extends('layouts.app')

@section('template_title')
    {{ __('Create') }} Colores
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="mx-auto w-full max-w-5xl">
            <div class="card card-default">
                <div class="card-header">
                    <span class="card-title">Crear color</span>
                </div>
                <div class="card-body bg-white">
                    <form method="POST" action="{{ route('colores.store') }}" role="form">
                        @csrf
                        @include('colores.form')
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
