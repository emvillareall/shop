@extends('layouts.ecommerce', ['title' => 'Checkout | Booty Fitness'])

@section('content')
    <x-ecommerce.breadcrumb :items="[
        ['label' => 'Inicio', 'url' => route('ecommerce.home')],
        ['label' => 'Carrito', 'url' => route('ecommerce.carrito.index')],
        ['label' => 'Checkout'],
    ]" />

    <section class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-900">Checkout</h1>
        <p class="mt-1 text-sm text-slate-600">Completa tus datos y elige metodo de pago para confirmar tu pedido.</p>
    </section>

    <livewire:ecommerce.checkout />
@endsection

