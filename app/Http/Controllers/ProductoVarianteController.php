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
        $producto = Producto::query()
            ->visibleEcommerce()
            ->findOrFail($producto->getKey());

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
                'colores.nombre_color',
                'colores.codigo_color',
            ])
            ->get();

        $variantes->transform(function ($variante) {
            $variante->nombre_color = $variante->nombre_color ?: 'Color #' . $variante->colores_id;
            $variante->codigo_color = $variante->codigo_color ?: '#94a3b8';

            return $variante;
        });

        return response()->json($variantes);
    }
}
