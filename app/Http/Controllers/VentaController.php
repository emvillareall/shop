<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Services\Ventas\AbonoService;
use Illuminate\Http\Request;

class VentaController extends Controller
{
    public function __construct(private readonly AbonoService $abonoService) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $estado = (string) $request->query('estado', '');

        $ventas = Venta::query()
            ->leftJoin('pedidos', 'pedidos.id', '=', 'ventas.pedido_id')
            ->leftJoin('clientes', 'clientes.id', '=', 'ventas.clientes_id')
            ->leftJoin('tiendas', 'tiendas.id', '=', 'ventas.tienda_id')
            ->select(
                'ventas.*',
                'pedidos.codigo_pedido',
                'pedidos.estado_pedido',
                'pedidos.estado_pago',
                'pedidos.estado_envio',
                'clientes.nombres_clientes',
                'clientes.apellidos_clientes',
                'tiendas.nombre_tienda',
                \DB::raw('(select coalesce(sum(va.monto),0) from venta_abonos va where va.venta_id = ventas.id) as total_abonado')
            )
            ->when($estado !== '', fn($query) => $query->where('ventas.estado_venta', $estado))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('pedidos.codigo_pedido', 'like', "%{$q}%")
                        ->orWhere('clientes.nombres_clientes', 'like', "%{$q}%")
                        ->orWhere('clientes.apellidos_clientes', 'like', "%{$q}%")
                        ->orWhere('tiendas.nombre_tienda', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('ventas.id')
            ->paginate(20)
            ->withQueryString();

        return view('venta.index', compact('ventas', 'q', 'estado'));
    }

    public function registrarAbono(Request $request, Venta $venta)
    {
        $validated = $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'metodo' => 'required|in:efectivo,transferencia,paypal,payphone',
            'referencia' => 'nullable|string|max:120',
            'observacion' => 'nullable|string|max:500',
        ]);

        $this->abonoService->registrarAbono(
            $venta,
            (float) $validated['monto'],
            (string) $validated['metodo'],
            $validated['referencia'] ?? null,
            $validated['observacion'] ?? null,
            auth()->id()
        );

        return back()->with('success', 'Abono registrado correctamente.');
    }
}
