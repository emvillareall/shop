@extends('layouts.app')

@section('template_title')
    {{ __('Create') }} Tienda
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="mx-auto w-full max-w-6xl">
            @includeif('partials.errors')

            <div class="card card-default">
                <div class="card-header">
                    <span class="card-title">Crear tienda</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('tiendas.store') }}" role="form" enctype="multipart/form-data">
                        @csrf
                        @include('tienda.form')
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
