<?php

namespace App\Http\Controllers;

use App\Models\StockReserva;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockReservaMetricsController extends Controller
{
    public function index(): View
    {
        $now = now();

        $resumen = StockReserva::query()
            ->where('expires_at', '>', $now)
            ->selectRaw('COUNT(*) as lineas_activas, COALESCE(SUM(cantidad),0) as unidades_reservadas, COUNT(DISTINCT session_id) as sesiones_activas')
            ->first();

        $porVariante = StockReserva::query()
            ->from('stock_reservas as sr')
            ->join('productos as p', 'p.id', '=', 'sr.producto_id')
            ->join('colores as c', 'c.id', '=', 'sr.color_id')
            ->leftJoin('colores_productos as cp', function ($join) {
                $join->on('cp.producto_id', '=', 'sr.producto_id')
                    ->on('cp.colores_id', '=', 'sr.color_id')
                    ->on('cp.talla_por_color', '=', 'sr.talla');
            })
            ->where('sr.expires_at', '>', $now)
            ->groupBy('sr.producto_id', 'p.descripcion_producto', 'sr.color_id', 'c.nombre_color', 'c.codigo_color', 'sr.talla', 'cp.stock_por_color')
            ->orderByRaw('SUM(sr.cantidad) DESC')
            ->select([
                'sr.producto_id',
                'p.descripcion_producto as producto',
                'sr.color_id',
                'c.nombre_color',
                'c.codigo_color',
                'sr.talla',
                DB::raw('COALESCE(cp.stock_por_color,0) as stock_actual'),
                DB::raw('SUM(sr.cantidad) as unidades_reservadas'),
                DB::raw('COUNT(DISTINCT sr.session_id) as sesiones'),
                DB::raw('MIN(sr.expires_at) as primer_expira'),
            ])
            ->paginate(30);

        return view('admin.stock-reservas.index', [
            'resumen' => $resumen,
            'porVariante' => $porVariante,
        ]);
    }
}

