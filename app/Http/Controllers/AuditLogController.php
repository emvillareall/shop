<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    private const DOMAIN_RULES = [
        'inventario' => [
            'acciones_like' => ['inventario.%'],
            'entidades' => ['inventario_movimientos', 'colores_productos', 'compra_items'],
        ],
        'pedidos' => [
            'acciones_like' => ['pedido.%'],
            'entidades' => ['pedidos', 'detalle_pedidos'],
        ],
        'pagos' => [
            'acciones_like' => ['pago.%'],
            'entidades' => ['pagos', 'payment_webhook_events'],
        ],
        'compras' => [
            'acciones_like' => ['compra.%', 'compra_item.%'],
            'entidades' => ['compras', 'compra_items'],
        ],
    ];

    private function buildQuery(Request $request)
    {
        $query = AuditLog::query();

        $domain = (string) $request->input('dominio', '');
        if ($domain && isset(self::DOMAIN_RULES[$domain])) {
            $rule = self::DOMAIN_RULES[$domain];
            $query->where(function ($q) use ($rule) {
                foreach ($rule['acciones_like'] as $like) {
                    $q->orWhere('accion', 'like', $like);
                }
                $q->orWhereIn('entidad', $rule['entidades']);
            });
        }

        if ($request->filled('accion')) {
            $query->where('accion', 'like', '%' . $request->string('accion') . '%');
        }
        if ($request->filled('entidad')) {
            $query->where('entidad', 'like', '%' . $request->string('entidad') . '%');
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }
        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->input('hasta'));
        }

        return $query;
    }

    public function index(Request $request)
    {
        $baseQuery = $this->buildQuery($request);
        $logs = (clone $baseQuery)->orderByDesc('id')->paginate(50)->withQueryString();

        $totalEventos = (clone $baseQuery)->count();
        $ultimas24h = (clone $baseQuery)->where('created_at', '>=', now()->subDay())->count();

        $topAcciones = (clone $baseQuery)
            ->selectRaw('accion, COUNT(*) as total')
            ->groupBy('accion')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $topUsuarios = (clone $baseQuery)
            ->selectRaw('COALESCE(user_id, 0) as uid, COUNT(*) as total')
            ->groupBy('uid')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $porDominio = (clone $baseQuery)
            ->selectRaw("
                CASE
                    WHEN accion LIKE 'inventario.%' OR entidad IN ('inventario_movimientos','colores_productos','compra_items') THEN 'inventario'
                    WHEN accion LIKE 'pedido.%' OR entidad IN ('pedidos','detalle_pedidos') THEN 'pedidos'
                    WHEN accion LIKE 'pago.%' OR entidad IN ('pagos','payment_webhook_events') THEN 'pagos'
                    WHEN accion LIKE 'compra.%' OR accion LIKE 'compra_item.%' OR entidad IN ('compras','compra_items') THEN 'compras'
                    ELSE 'otros'
                END as dominio,
                COUNT(*) as total
            ")
            ->groupBy('dominio')
            ->orderByDesc('total')
            ->get();

        $tendencia = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
            ->groupBy('fecha')
            ->orderByDesc('fecha')
            ->limit(14)
            ->get()
            ->sortBy('fecha')
            ->values();

        return view('auditoria.index', compact(
            'logs',
            'totalEventos',
            'ultimas24h',
            'topAcciones',
            'topUsuarios',
            'porDominio',
            'tendencia'
        ));
    }

    public function exportCsv(Request $request)
    {
        $rows = $this->buildQuery($request)->limit(5000)->get();
        $filename = 'auditoria_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'fecha', 'user_id', 'accion', 'entidad', 'entidad_id', 'ip', 'antes_json', 'despues_json']);

            foreach ($rows as $log) {
                fputcsv($out, [
                    $log->id,
                    optional($log->created_at)->format('Y-m-d H:i:s'),
                    $log->user_id,
                    $log->accion,
                    $log->entidad,
                    $log->entidad_id,
                    $log->ip,
                    json_encode($log->valores_antes, JSON_UNESCAPED_UNICODE),
                    json_encode($log->valores_despues, JSON_UNESCAPED_UNICODE),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
