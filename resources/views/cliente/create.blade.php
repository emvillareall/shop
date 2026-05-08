@extends('layouts.app_cliente')

@section('template_title')
    Crear cliente
@endsection

@section('content')
    <section class="mx-auto w-full max-w-4xl">
        <div class="rounded-2xl border border-brand-100 bg-white shadow-sm">
            <header class="border-b border-brand-100 bg-brand-50/70 px-5 py-4 md:px-6">
                <h1 class="text-lg font-bold text-slate-900 md:text-xl">Datos de envio</h1>
                <p class="mt-1 text-sm text-slate-600">Completa la informacion para vincular este pedido con el cliente.</p>
            </header>

            <div class="px-5 py-5 md:px-6">
                <form method="POST" action="{{ url()->full() }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    {{ Form::hidden('id_pedido', $id) }}
                    {{ Form::hidden('id', $id) }}

                    @include('cliente.form')
                </form>
            </div>
        </div>
    </section>
@endsection
