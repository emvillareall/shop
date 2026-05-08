@extends('layouts.app_cliente')

@section('content')

<style>
  .producto-card {
    background: #ffffff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.08);
    padding: 20px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    height: 100%;
  }

  .producto-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.12);
  }

  .producto-img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 15px;
  }

  .producto-titulo {
    font-weight: 600;
    font-size: 18px;
  }

  .producto-precio {
    font-size: 16px;
    color: #0d6efd;
    font-weight: bold;
  }

  .producto-desc {
    font-size: 14px;
    color: #555;
  }
</style>

<div class="container my-5">
  <h2 class="text-center mb-4">Productos en <strong>{{ $categoria->nombre_categoria }}</strong> ({{ $linea->nombre_linea }})</h2>

  <div class="row">
    @forelse($productos as $producto)
      <div class="col-md-4 mb-4">
        <div class="producto-card">
          @if($producto->imagen_producto)
            <img src="{{ asset('storage/' . $producto->imagen_producto) }}" alt="Imagen de {{ $producto->descripcion_producto }}" class="producto-img">
          @else
            <img src="{{ asset('images/default.jpg') }}" alt="Sin imagen" class="producto-img">
          @endif

          <div class="producto-titulo">{{ $producto->descripcion_producto }}</div>
          <div class="producto-desc">Código: {{ $producto->codigo_producto }}</div>
          <div class="producto-precio">${{ number_format($producto->precio_venta_producto, 2) }}</div>
        </div>
      </div>
    @empty
      <div class="col-12 text-center">
        <p>No hay productos activos para esta categoría.</p>
      </div>
    @endforelse
  </div>
</div>

@endsection
