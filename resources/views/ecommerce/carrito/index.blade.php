@extends('layouts.ecommerce', ['title' => 'Carrito | Booty Fitness'])

@section('content')
    <x-ecommerce.breadcrumb :items="[
        ['label' => 'Inicio', 'url' => route('ecommerce.home')],
        ['label' => 'Carrito'],
    ]" />

    <section class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-900">Carrito de compras</h1>
        <p class="mt-1 text-sm text-slate-600">Revisa cantidades por variante antes de pasar al checkout.</p>
    </section>

    <livewire:ecommerce.carrito-compras />
@endsection

