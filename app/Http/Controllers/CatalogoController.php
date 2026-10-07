<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;


class CatalogoController extends Controller
{
    public function catalogoGeneral()
    {
        return redirect()->route('ecommerce.productos.index');
    }

    public function descargarCatalogoPDF()
{
    $lineas = DB::table('lineas_ropa')
        ->where('estado_linea', '1')
        ->orderBy('nombre_linea')
        ->get();

    $catalogo = [];

    foreach ($lineas as $linea) {
        $productos = $this->productosVisiblesPorLinea((int) $linea->id);
        $this->cargarVariantes($productos);

        $catalogo[] = [
            'linea' => $linea,
            'productos' => $productos
        ];
    }

    $pdf = Pdf::loadView('catalogo.general_pdf', compact('catalogo'))->setPaper('a4', 'portrait');
    return $pdf->download('catalogo_deportivo.pdf');
}

    private function productosVisiblesPorLinea(int $lineaId)
    {
        return Producto::query()
            ->visibleEcommerce()
            ->join('categorias_productos', 'productos.categoria_producto_id', '=', 'categorias_productos.id')
            ->join('lineas_ropa', 'categorias_productos.linea_ropa_id', '=', 'lineas_ropa.id')
            ->where('lineas_ropa.id', $lineaId)
            ->select(
                'productos.id',
                'productos.descripcion_producto',
                'productos.imagen_producto',
                'productos.precio_venta_producto'
            )
            ->get();
    }

    private function cargarVariantes($productos): void
    {
        foreach ($productos as $producto) {
            $producto->variantes = DB::table('colores_productos')
                ->join('colores', 'colores_productos.colores_id', '=', 'colores.id')
                ->where('producto_id', $producto->id)
                ->select(
                    'colores.nombre_color',
                    'colores.codigo_color',
                    'colores_productos.talla_por_color',
                    'colores_productos.stock_por_color'
                )
                ->orderBy('talla_por_color')
                ->get();
        }
    }
}
