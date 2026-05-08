@extends('layouts.app')

@section('template_title')
    Detalle de Pedido
@endsection

@section('content')
    <div class="space-y-4">
        @if ($message = Session::get('danger'))
            <div class="alert alert-danger">{{ $message }}</div>
        @endif

        @includeif('partials.errors')

        <div class="card">
            <div class="card-header">
                <span class="text-base font-semibold">Agregar productos al pedido</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('detalle-pedidos.store') }}" role="form" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="return_to" value="{{ request('return_to') }}">
                    @include('detalle-pedido.form')
                </form>
            </div>
        </div>
    </div>
@endsection
