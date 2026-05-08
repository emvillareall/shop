@extends('layouts.app')

@section('template_title')
    Editar Pedido
@endsection

@section('content')
    <div class="max-w-3xl space-y-4">
        @includeif('partials.errors')

        <div class="card">
            <div class="card-header">
                <span class="text-base font-semibold">Editar Pedido #{{ $pedido->id }}</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('pedidos.update', $pedido->id) }}" role="form" enctype="multipart/form-data" class="space-y-4">
                    @method('PATCH')
                    @csrf
                    @include('pedido.form')
                </form>
            </div>
        </div>
    </div>
@endsection
