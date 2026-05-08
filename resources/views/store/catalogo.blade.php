@extends('layouts.app_cliente')

@section('content')
<div class="mx-auto max-w-[1820px] space-y-6">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Featured Products</h1>
                <div class="mt-2 h-1 w-16 rounded-full bg-purple-400"></div>
            </div>
            <a href="{{ route('ecommerce.carrito.index') }}" class="inline-flex items-center rounded-full bg-purple-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-purple-700">
                Ver carrito
            </a>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
            @foreach($productos as $producto)
                @include('store.partials.product-card', ['producto' => $producto, 'reservado' => $reservado])
            @endforeach
        </div>
    </section>
</div>
@endsection
