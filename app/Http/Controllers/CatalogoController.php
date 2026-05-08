<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;


class CatalogoController extends Controller
{
    public function catalogoGeneral()
    {
        $lineas = DB::table('lineas_ropa')
            ->where('estado_linea', '1')
            ->orderBy('nombre_linea')
            ->get();

        $catalogo = [];

        foreach ($lineas as $linea) {
            // Productos por línea
            $productos = DB::table('productos')
                ->join('categorias_productos', 'productos.categoria_producto_id', '=', 'categorias_productos.id')
                ->join('lineas_ropa', 'categorias_productos.linea_ropa_id', '=', 'lineas_ropa.id')
                ->where('lineas_ropa.id', $linea->id)
                ->where('productos.estado_producto', '1')
                ->select(
                    'productos.id',
                    'productos.descripcion_producto',
                    'productos.imagen_producto',
                    'productos.precio_venta_producto'
                )
                ->get();

            foreach ($productos as $producto) {
                // Variantes de colores, tallas y stock
                $variantes = DB::table('colores_productos')
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

                $producto->variantes = $variantes;
            }

            $catalogo[] = [
                'linea' => $linea,
                'productos' => $productos
            ];
        }

        return view('catalogo.general', compact('catalogo'));
    }

    public function descargarCatalogoPDF()
{
    $lineas = DB::table('lineas_ropa')
        ->where('estado_linea', '1')
        ->orderBy('nombre_linea')
        ->get();

    $catalogo = [];

    foreach ($lineas as $linea) {
        $productos = DB::table('productos')
            ->join('categorias_productos', 'productos.categoria_producto_id', '=', 'categorias_productos.id')
            ->join('lineas_ropa', 'categorias_productos.linea_ropa_id', '=', 'lineas_ropa.id')
            ->where('lineas_ropa.id', $linea->id)
            ->where('productos.estado_producto', '1')
            ->select('productos.id', 'productos.descripcion_producto', 'productos.imagen_producto', 'productos.precio_venta_producto')
            ->get();

        foreach ($productos as $producto) {
            $variantes = DB::table('colores_productos')
                ->join('colores', 'colores_productos.colores_id', '=', 'colores.id')
                ->where('producto_id', $producto->id)
                ->select('colores.nombre_color', 'colores.codigo_color', 'colores_productos.talla_por_color', 'colores_productos.stock_por_color')
                ->get();

            $producto->variantes = $variantes;
        }

        $catalogo[] = [
            'linea' => $linea,
            'productos' => $productos
        ];
    }

    $pdf = Pdf::loadView('catalogo.general_pdf', compact('catalogo'))->setPaper('a4', 'portrait');
    return $pdf->download('catalogo_deportivo.pdf');
}
}
