<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Catálogo Deportivo PDF</title>
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; margin: 30px; color: #222; background: #f8f9fa; }
    .page-break { page-break-before: always; }
    .portada { text-align: center; margin-top: 80px; }
    .portada h1 { font-size: 32px; letter-spacing: 2px; font-weight: 700; text-transform: uppercase; }
    .portada h2 { font-size: 18px; color: #d41c5e; margin-top: 10px; font-weight: 600; }
    .portada p { margin-top: 20px; font-size: 13px; color: #666; font-style: italic; }
    .indice { margin: 20px 0; }
    .indice h2 { font-size: 18px; font-weight: 700; text-transform: uppercase; margin-bottom: 12px; border-bottom: 3px solid #d41c5e; padding-bottom: 4px; }
    .indice ul { list-style: none; padding-left: 0; }
    .indice li { margin-bottom: 6px; font-size: 13px; font-weight: 600; color: #555; }
    h2.linea-titulo { font-size: 20px; font-weight: 700; color: #d41c5e; margin: 30px 0 15px; border-left: 5px solid #d41c5e; padding-left: 10px; text-transform: uppercase; letter-spacing: 1px; }
    table.catalogo { width: 100%; border-spacing: 12px 18px; }
    td { vertical-align: top; text-align: center; }
    .producto-card { background: #fff; border-radius: 8px; box-shadow: 0 3px 8px rgba(0,0,0,0.1); width: 180px; padding: 10px; display: flex; flex-direction: column; align-items: center; height: 350px; margin: 0 auto; }
    .producto-card img { width: 100%; height: 170px; object-fit: cover; border-radius: 6px; margin-bottom: 8px; border: 1px solid #ddd; }
    .producto-card h3 { font-size: 13px; font-weight: 700; margin: 0 0 6px; text-align: center; min-height: 38px; line-height: 1.2; }
    .price { font-weight: 700; font-size: 16px; color: #d41c5e; margin-bottom: 10px; }
    .variantes-info { width: 100%; font-size: 10px; color: #444; margin-top: auto; display: flex; flex-wrap: nowrap; overflow-x: auto; gap: 10px; justify-content: center; padding-bottom: 4px; }
    .var-item { display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0; }
    .color-circle { display: inline-block; width: 33px; height: 32px; border-radius: 50%; border: 1px solid #aaa; background-color: #ccc; position: relative; }
    .color-circle.out-of-stock { background-color: #f8d7da; border-color: #a94442; }
    .size-text, .stock-text-small { position: absolute; left: 50%; transform: translateX(-50%); font-weight: 700; font-size: 8px; }
    .size-text { top: 8px; }
    .stock-text-small { bottom: 6px; }
    .light-text { color: #000; }
    .dark-text { color: #fff; }
    .footer { text-align: center; font-size: 9px; margin-top: 40px; border-top: 1px solid #ccc; padding-top: 8px; color: #999; }
</style>
</head>
<body>

<div class="portada">
    <h1>Catálogo Deportivo</h1>
    <h2>Colección Dama - Temporada {{ date('Y') }}</h2>
    <p>Hecho para moverte. Diseñado para inspirarte.</p>
</div>

<div class="page-break"></div>

<div class="indice">
    <h2>Tabla de Contenido</h2>
    <ul>
        @foreach($catalogo as $i => $bloque)
            <li>{{ $i + 1 }}. {{ $bloque['linea']->nombre_linea }}</li>
        @endforeach
    </ul>
</div>

@foreach($catalogo as $bloque)
    <div class="page-break"></div>
    <h2 class="linea-titulo">{{ $bloque['linea']->nombre_linea }}</h2>
    <table class="catalogo">
        <tr>
            @php $count = 0; @endphp
            @foreach($bloque['productos'] as $producto)
                <td>
                    <div class="producto-card">
                        @if($producto->imagen_producto)
                            <img src="{{ public_path('storage/' . $producto->imagen_producto) }}" alt="Imagen producto"/>
                        @else
                            <div style="width:100%; height:170px; background:#eee; text-align:center; line-height:170px; color:#999; border-radius:6px;">Sin imagen</div>
                        @endif
                        <h3>{{ $producto->descripcion_producto }}</h3>
                        <p class="price">$ {{ number_format($producto->precio_venta_producto, 2) }}</p>
                        <div class="variantes-info">
                            @foreach($producto->variantes as $v)
                                @php
                                    list($r,$g,$b) = sscanf($v->codigo_color, "#%02x%02x%02x");
                                    $brightness = ($r*299 + $g*587 + $b*114) / 1000;
                                    $textClass = $brightness > 200 ? 'light-text' : 'dark-text';
                                @endphp
                                <div class="var-item">
                                    <span class="color-circle {{ $v->stock_por_color <= 0 ? 'out-of-stock' : '' }}" style="background-color: {{ $v->codigo_color }};">
                                        <span class="size-text {{ $textClass }}">{{ $v->talla_por_color }}</span>
                                        <span class="stock-text-small {{ $textClass }}">{{ $v->stock_por_color }}</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </td>
                @php $count++; @endphp
                @if($count % 3 === 0)
                    </tr><tr>
                @endif
            @endforeach
            @for($i = $count % 3; $i>0 && $i<3; $i++)
                <td></td>
            @endfor
        </tr>
    </table>
@endforeach

<div class="footer">
    &copy; {{ date('Y') }} TuMarcaFit - Catálogo generado automáticamente.
</div>

</body>
</html>
