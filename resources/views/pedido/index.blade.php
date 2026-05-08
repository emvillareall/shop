@extends('layouts.app')

@section('template_title')
    Pedidos
@endsection

@section('content')
    <div class="space-y-4">
        <div class="card">
            <div class="card-header">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-base font-semibold">Gestión de Pedidos</span>
                    <div class="flex items-center gap-2">
                        <a class="btn btn-sm btn-warning" href="{{ route('getPDF_pedidos_completo') }}" target="_blank">
                            <i class="fa fa-fw fa-print"></i>
                        </a>
                        <a href="{{ route('pedidos.create') }}" class="btn btn-sm btn-primary">
                            Crear Pedido
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if ($message = Session::get('success'))
            <div class="alert alert-success">{{ $message }}</div>
        @elseif($message = Session::get('danger'))
            <div class="alert alert-danger">{{ $message }}</div>
        @endif

        <livewire:pedidos-table :url-signed="isset($url_signed) ? $url_signed : null" :highlight-pedido-id="isset($id) ? (int)$id : null" />
    </div>
@endsection
