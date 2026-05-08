@extends('layouts.app')

@section('template_title')
    {{ __('Update') }} Proveedore
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="mx-auto w-full max-w-6xl">
            @includeif('partials.errors')

            <div class="card card-default">
                <div class="card-header">
                    <span class="card-title">Editar proveedor</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('proveedores.update', $proveedore->id) }}" role="form" enctype="multipart/form-data">
                        {{ method_field('PATCH') }}
                        @csrf
                        @include('proveedore.form')
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
