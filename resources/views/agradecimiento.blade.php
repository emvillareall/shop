@extends('layouts.app_cliente')

@section('template_title')
    Pedido recibido
@endsection

@section('content')
    <section class="mx-auto w-full max-w-2xl">
        <div class="rounded-2xl border border-emerald-200 bg-white shadow-sm">
            <header class="border-b border-emerald-100 bg-emerald-50 px-5 py-4 md:px-6">
                <h1 class="text-lg font-bold text-slate-900 md:text-xl">Datos enviados correctamente</h1>
                <p class="mt-1 text-sm text-slate-600">Gracias, tu pedido ya quedo listo para preparacion y envio.</p>
            </header>
            <div class="px-5 py-5 md:px-6">
                <a href="{{ route('ecommerce.home') }}" class="btn btn-primary">Ir a la tienda</a>
            </div>
        </div>
    </section>
@endsection

