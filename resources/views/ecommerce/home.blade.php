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

    <section class="mb-8">
        <div class="mb-3 flex items-end justify-between gap-2">
            <h2 class="text-xl font-bold">Categorias</h2>
            <p class="text-xs text-slate-500">{{ $categorias->count() }} activas</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @forelse($categorias as $categoria)
                <a href="{{ route('ecommerce.categoria.show', $categoria->id) }}" class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold shadow-sm transition hover:border-brand-300 hover:shadow">
                    {{ $categoria->nombre_categoria }}
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-500">No hay categorias activas.</div>
            @endforelse
        </div>
    </section>

    <section>
        <div class="mb-3 flex items-end justify-between gap-2">
            <h2 class="text-xl font-bold">Destacados</h2>
            <a href="{{ route('ecommerce.productos.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">Explorar todo</a>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($destacados as $producto)
                <x-ecommerce.product-card :producto="$producto" />
            @endforeach
        </div>
    </section>
@endsection

