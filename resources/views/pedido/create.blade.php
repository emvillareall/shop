@extends('layouts.app')

@section('template_title')
    Nuevo Pedido
@endsection

@section('content')
    <div class="max-w-3xl space-y-4">
        @includeif('partials.errors')
        @if(session('link_generado'))
            <div class="card border-brand-200">
                <div class="card-body space-y-3">
                    <div class="text-sm font-semibold text-slate-700">Enlace generado para pedido #{{ session('link_pedido_id') }}</div>
                    <div class="flex items-center gap-2">
                        <input id="pedido-link-generado" type="text" readonly value="{{ session('link_generado') }}">
                        <button type="button" class="btn btn-sm btn-dark" onclick="navigator.clipboard.writeText(document.getElementById('pedido-link-generado').value)">Copiar</button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if(session('whatsapp_url') && session('modo_envio_link', 'whatsapp') === 'whatsapp')
                            <a class="btn btn-sm btn-success" href="{{ session('whatsapp_url') }}" target="_blank">Enviar por WhatsApp</a>
                        @endif
                        @if(session('modo_envio_link') === 'copiar')
                            <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">Listo para compartir por cualquier medio</span>
                        @endif
                        <a href="{{ route('detalle-pedidos.create', ['id' => session('link_pedido_id')]) }}" class="btn btn-sm btn-primary">Agregar productos al pedido</a>
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <span class="text-base font-semibold">Nuevo Pedido</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('pedidos.store') }}" role="form" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @include('pedido.form')
                </form>
            </div>
        </div>
    </div>
@endsection
