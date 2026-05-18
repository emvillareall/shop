@extends('layouts.ecommerce', ['title' => 'Inicio | Booty Fitness'])

@section('content')
    <section class="mb-8 rounded-2xl border border-brand-100 bg-white px-6 py-7 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">Boutique online</p>
        <h1 class="mt-2 text-3xl font-extrabold text-slate-900 md:text-4xl">BootyFitness Store</h1>
        <p class="mt-3 max-w-2xl text-sm text-slate-600 md:text-base">Compra ropa fitness con stock real por variante, pagos controlados y una experiencia clara en movil y escritorio.</p>
        <div class="mt-5 flex flex-wrap gap-3">
            <a href="{{ route('ecommerce.productos.index') }}" class="btn btn-primary">Ver catalogo</a>
            <a href="{{ route('ecommerce.carrito.index') }}" class="btn btn-secondary">Ir al carrito</a>
        </div>
    </section>

    <livewire:ecommerce.catalogo-productos
        mode="home"
        :linea-id="isset($lineaSeleccionada) ? $lineaSeleccionada : null"
        :categoria-id="isset($categoriaSeleccionada) ? $categoriaSeleccionada : null"
        :search-term="isset($searchTerm) ? $searchTerm : ''"
    />
@endsection
