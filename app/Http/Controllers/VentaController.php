<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;

class VentaController extends Controller
{
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
                'clientes.nombres_clientes',
                'clientes.apellidos_clientes',
                'tiendas.nombre_tienda'
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
}

