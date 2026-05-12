<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApartadoController extends Controller
{
    public function index(Request $request)
    {
        $apartadosQuery = DB::table('ventas as v')
            ->join('pedidos as p', 'p.id', '=', 'v.pedido_id')
            ->join('clientes as c', 'c.id', '=', 'v.clientes_id')
            ->select(
                'v.id',
                'v.pedido_id',
                'v.total',
                'v.fecha_proximo_abono',
                DB::raw('DATEDIFF(CURDATE(), DATE(v.fecha_proximo_abono)) as dias_atraso'),
                DB::raw("
                    CASE
                        WHEN v.fecha_proximo_abono IS NULL THEN 4
                        WHEN DATE(v.fecha_proximo_abono) <= CURDATE() THEN 1
                        WHEN YEARWEEK(v.fecha_proximo_abono, 1) = YEARWEEK(CURDATE(), 1) THEN 2
                        ELSE 3
                    END as prioridad_orden
                "),
                'p.codigo_pedido',
                'p.descripcion',
                'c.nombres_clientes',
                'c.apellidos_clientes',
                DB::raw('(select coalesce(sum(va.monto),0) from venta_abonos va where va.venta_id = v.id) as total_abonado')
            )
            ->where('v.modalidad_venta', 'APARTADO')
            ->whereIn('v.estado_venta', ['PENDIENTE_ABONO', 'PENDIENTE']);

        $apartados = (clone $apartadosQuery)
            ->orderBy('prioridad_orden')
            ->orderByRaw('COALESCE(v.fecha_proximo_abono, v.created_at) asc')
            ->paginate(20)
            ->withQueryString()
            ->through(function ($row) {
                $row->saldo = max(0, (float) $row->total - (float) $row->total_abonado);
                return $row;
            });

        $hoy = now()->startOfDay();
        $finSemana = now()->copy()->endOfWeek();
        $apartadosPendientes = (clone $apartadosQuery)->count();
        $apartadosVencidosHoy = (clone $apartadosQuery)
            ->whereNotNull('v.fecha_proximo_abono')
            ->whereDate('v.fecha_proximo_abono', '<=', $hoy->toDateString())
            ->count();
        $apartadosSemana = (clone $apartadosQuery)
            ->whereNotNull('v.fecha_proximo_abono')
            ->whereBetween('v.fecha_proximo_abono', [$hoy->toDateTimeString(), $finSemana->toDateTimeString()])
            ->count();
        $saldoTotalApartados = (clone $apartadosQuery)
            ->get()
            ->sum(fn ($r) => max(0, (float) $r->total - (float) $r->total_abonado));

        $apartadosMetrics = [
            'pendientes' => (int) $apartadosPendientes,
            'vencidos_hoy' => (int) $apartadosVencidosHoy,
            'semana' => (int) $apartadosSemana,
            'saldo_total' => (float) $saldoTotalApartados,
        ];

        return view('apartados.index', compact('apartados', 'apartadosMetrics'));
    }
}

