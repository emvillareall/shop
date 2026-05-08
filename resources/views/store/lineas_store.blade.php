@extends('layouts.app_cliente')

@section('content')

<style>
  body {
    background: linear-gradient(135deg, #1f1c2c, #928dab);
    font-family: 'Poppins', sans-serif;
    color: white;
  }

.section-title {
  font-size: 3rem;
  font-weight: 800;
  text-align: center;
  margin-bottom: 3rem;
  position: relative;
  color: black;
}

  .section-title::after {
    content: '';
    display: block;
    width: 80px;
    height: 5px;
    background: linear-gradient(90deg, #d9a7c7, #fffcdc);
    margin: 12px auto 0;
    border-radius: 50px;
  }

  .linea-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    overflow: hidden;
    transition: transform 0.4s ease, box-shadow 0.4s ease;
    height: 100%;
    border: 1px solid rgba(255, 255, 255, 0.1);
  }

  .linea-card:hover {
    transform: scale(1.03);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.4);
  }

  .linea-img {
    height: 260px;
    width: 100%;
    object-fit: cover;
    transition: transform 0.5s ease-in-out;
  }

  .linea-card:hover .linea-img {
    transform: scale(1.08);
  }

  .linea-body {
    padding: 25px;
    text-align: center;
  }

.linea-title {
  font-size: 22px;
  font-weight: 700;
  color: black;
  margin-bottom: 12px;
}

  .linea-desc {
    font-size: 15px;
    color: #e0e0e0;
    margin-bottom: 20px;
    height: 48px;
    overflow: hidden;
  }

.ver-btn {
  background: linear-gradient(135deg, #c471f5, #fa71cd);
  color: white !important;
  padding: 12px 28px;
  border: none;
  border-radius: 50px;
  font-weight: bold;
  letter-spacing: 0.5px;
  transition: all 0.3s ease;
  text-decoration: none;
  display: inline-block;
}

.ver-btn:hover {
  background: linear-gradient(135deg, #b56ce0, #d594ed);
  color: white !important;
  transform: scale(1.05);
}

  .empty-message {
    text-align: center;
    color: #ccc;
    font-size: 20px;
    padding: 60px;
  }

  .btn-custom-morado {
    background: linear-gradient(135deg, #a85cf9, #d67be8);
    color: #fff;
    border: none;
  }

  .btn-custom-morado:hover {
    background: linear-gradient(135deg, #8e3fe0, #b85fdc);
    color: #fff;
  }
</style>

<div class="container py-5">
  <h2 class="section-title">Nuestras Líneas Exclusivas</h2>
  <div class="row justify-content-center">
    @php
      $imagenEjemplo = 'https://images.unsplash.com/photo-1605296867304-46d5465a13f1?auto=format&fit=crop&w=800&q=80';
    @endphp

    @forelse($lineas as $linea)
      <div class="col-lg-4 col-md-6 mb-4 d-flex">
        <div class="linea-card w-100">
          <img src="{{ $linea->imagen ? asset('storage/' . $linea->imagen) : $imagenEjemplo }}" 
               alt="{{ $linea->nombre_linea }}" 
               class="linea-img">
          <div class="linea-body">
            <h5 class="linea-title">{{ $linea->nombre_linea }}</h5>
            <p class="linea-desc">{{ $linea->descripcion }}</p>
            <a href="{{ route('store.categorias', ['id' => $linea->id]) }}" class="ver-btn">Ver Categorías</a>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12">
        <p class="empty-message">🚫 No hay líneas disponibles en este momento.</p>
      </div>
    @endforelse
  </div>
</div>

@endsection
