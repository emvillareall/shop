<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductoVarianteController extends Controller
{
    public function admin(Producto $producto): JsonResponse
    {
        return $this->buildResponse($producto);
    }

    public function shop(Producto $producto): JsonResponse
    {
        return $this->buildResponse($producto);
    }

    private function buildResponse(Producto $producto): JsonResponse
    {
        $variantes = DB::table('colores_productos')
            ->leftJoin('colores', 'colores_productos.colores_id', '=', 'colores.id')
            ->where('colores_productos.producto_id', $producto->id)
            ->where('colores_productos.talla_por_color', '!=', '')
            ->whereNotNull('colores_productos.talla_por_color')
            ->orderByRaw('COALESCE(colores.nombre_color, "") asc')
            ->orderBy('colores_productos.talla_por_color')
            ->select([
                'colores_productos.id',
                'colores_productos.producto_id',
                'colores_productos.colores_id',
                'colores_productos.talla_por_color',
                'colores_productos.stock_por_color',
                DB::raw('COALESCE(colores.nombre_color, CONCAT("Color #", colores_productos.colores_id)) as nombre_color'),
                DB::raw('COALESCE(colores.codigo_color, "#94a3b8") as codigo_color'),
            ])
            ->get();

        return response()->json($variantes);
    }
}
