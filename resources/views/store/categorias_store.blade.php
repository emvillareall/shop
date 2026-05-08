@extends('layouts.app_cliente')

@section('content')

<style>
  body {
    background: linear-gradient(135deg, #1f1c2c, #928dab);
    font-family: 'Poppins', sans-serif;
    color: white;
    margin: 0;
    padding: 0;
  }

  h2 {
    color: black;
    font-weight: 800;
    margin-bottom: 3rem;
    text-align: center;
  }

  .hex-grande {
    width: auto;
    max-width: 600px;
    margin: 0 auto 40px auto;
    padding: 10px;
    position: relative;
  }

  .hex-row {
    display: flex;
    justify-content: center;
    margin-bottom: -14px; /* superposición vertical de hexágonos */
  }

  .hex-row.offset-1 {
    margin-left: 38px;
  }

  .hex-row.offset-2 {
    margin-left: 76px;
  }

  .categoria-hex {
    width: 75px;
    height: 65px;
    background: rgba(255, 255, 255, 0.3);
    clip-path: polygon(
      25% 0%, 75% 0%, 
      100% 50%, 75% 100%, 
      25% 100%, 0% 50%
    );
    margin: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: black;
    font-weight: 600;
    font-size: 0.8rem;
    text-align: center;
    cursor: pointer;
    user-select: none;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
    border: 1px solid rgba(255,255,255,0.15);
    opacity: 0.5;
    transition: all 0.3s ease;
    position: relative;
    background-size: cover;
    background-position: center;
  }

  .categoria-hex:hover {
    opacity: 1;
    background: linear-gradient(135deg, #a85cf9, #d67be8);
    color: white;
    box-shadow: 0 15px 40px rgba(168, 92, 249, 0.7);
    transform: scale(1.1);
    z-index: 10;
  }

  .categoria-link {
    text-decoration: none;
    color: inherit;
  }

  .empty-message {
    text-align: center;
    color: #ccc;
    font-size: 1.2rem;
    padding: 60px 0;
  }

  @media (max-width: 480px) {
    .categoria-hex {
      width: 50px;
      height: 43px;
      font-size: 0.6rem;
      margin: 2.5px;
    }

    .hex-row.offset-1 {
      margin-left: 25px;
    }

    .hex-row.offset-2 {
      margin-left: 50px;
    }
  }
</style>

<div class="container my-5">
  <h2>Categorías de la línea: <strong>{{ $linea->nombre_linea }}</strong></h2>

  @if($categorias->isEmpty())
    <p class="empty-message">🚫 No hay categorías disponibles para esta línea.</p>
  @else
    <div class="hex-grande" aria-label="Hexágono grande con categorías">

      @php
        $estructuraHex = [3, 4, 5, 6, 5, 4, 3];
        $offsets = [2, 1, 0, 0, 0, 1, 2];
        $index = 0;
      @endphp

      @foreach($estructuraHex as $fila => $hexCount)
        <div class="hex-row offset-{{ $offsets[$fila] }}">
          @for($i = 0; $i < $hexCount; $i++)
            @if($index >= count($categorias)) @break 2 @endif
            @php $cat = $categorias[$index++]; @endphp
            <a href="{{ route('lineas.productos', ['lineaId' => $linea->id, 'categoriaId' => $cat->id]) }}" class="categoria-link" title="{{ $cat->nombre_categoria }}">
              <div class="categoria-hex">
                {{ $cat->nombre_categoria }}
              </div>
            </a>
          @endfor
        </div>
      @endforeach

    </div>
  @endif
</div>

@endsection
