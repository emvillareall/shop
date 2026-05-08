<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proveedore;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\DetallePedido;
use App\Models\Producto;
use App\Models\Pedido;
use App\Models\Tienda;
use DB;

class ReportesController extends Controller
{
    public function index()
    {
        $repo = DB::table('detalle_pedidos')
    ->join('pedidos', 'pedidos.id', '=', 'detalle_pedidos.pedido_id')
    ->join('productos', 'productos.id', '=', 'detalle_pedidos.producto_id')
    ->select(
        'detalle_pedidos.pedido_id',
        'pedidos.descripcion',
        'pedidos.total_pedido',
        'pedidos.created_at',
        DB::raw('SUM(detalle_pedidos.cantidad_producto * productos.precio_dolares_producto) as total'),
        DB::raw('pedidos.total_pedido - SUM(detalle_pedidos.cantidad_producto * productos.precio_dolares_producto) as ganancia')
    )
    ->groupBy('detalle_pedidos.pedido_id','pedidos.descripcion','pedidos.total_pedido','pedidos.created_at')
    ->get();        
        //$pedidos = $repo->groupby('pedido_id');

        //dd($repo);

        return view('reportes.index', compact('repo'));
    }

    public function show($id)
    {

        $reportes = DB::table('detalle_pedidos')
            ->join('pedidos', 'pedidos.id', '=', 'detalle_pedidos.pedido_id')
            ->join('productos', 'productos.id', '=', 'detalle_pedidos.producto_id')
            ->select(
                'detalle_pedidos.*',
                'pedidos.*',
                'productos.*',)
            ->where('pedidos.id',$id)
            ->paginate(300);
            //dd($pedidos);

        $pedidos = Pedido::find($id);

        $clientes = DB::table('pedidos')
            ->join('clientes', 'clientes.id', '=', 'pedidos.clientes_id')
            ->select('pedidos.*','clientes.*')
            ->where('pedidos.id',$id)->first();

        //dd($reportes);

        return view('reportes.reporte_ventas', compact('reportes','clientes','pedidos'))
            ->with('i', (request()->input('page', 1) - 1) * $reportes->perPage());
    }
}
