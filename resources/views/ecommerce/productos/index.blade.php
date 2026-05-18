@extends('layouts.ecommerce', ['title' => 'Catalogo | Booty Fitness'])

@section('content')
    <x-ecommerce.breadcrumb :items="[
        ['label' => 'Inicio', 'url' => route('ecommerce.home')],
        ['label' => isset($categoria) ? $categoria->nombre_categoria : 'Catalogo'],
    ]" />

    <section class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-900">{{ isset($categoria) ? $categoria->nombre_categoria : 'Catalogo de productos' }}</h1>
        <p class="mt-1 text-sm text-slate-600">Filtra por linea, categoria o busqueda para encontrar tu talla ideal.</p>
    </section>

    <livewire:ecommerce.catalogo-productos
        :categoria-id="isset($categoria) ? $categoria->id : (isset($categoriaId) ? $categoriaId : null)"
        :linea-id="isset($lineaId) ? $lineaId : null"
        :search-term="isset($searchTerm) ? $searchTerm : ''"
    />
@endsection
