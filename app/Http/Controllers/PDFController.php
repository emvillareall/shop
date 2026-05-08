<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\Tienda;
use Carbon\Carbon;
use DB;
use PDF;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Auditoria\AuditService;

class PDFController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

        public function getPDF_pedidos($id){
        if (!auth()->check()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $pedidos = Pedido::findOrFail($id);
        $cliente = Cliente::find($pedidos->clientes_id);
        $tienda = Tienda::find($pedidos->tienda_id);

        if (!$cliente || !$tienda) {
            abort(Response::HTTP_NOT_FOUND, 'No se encontraron datos del pedido para PDF.');
        }

        $pdf = PDF::loadView('pdf/pedidos', compact('cliente','tienda'));
        $this->auditService->log(
            'pdf.pedido.generado',
            'pedidos',
            $id,
            null,
            ['cliente_id' => $cliente->id, 'tienda_id' => $tienda->id]
        );
        return $pdf->stream('prueba_1.pdf');
    }
        public function getPDF_pedidos_completo(){
        if (!auth()->check()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $date = Carbon::now();
        $pedidos = DB::table('pedidos')
            ->join('tiendas', 'tiendas.id', '=', 'pedidos.tienda_id')
            ->join('clientes', 'clientes.id', '=', 'pedidos.clientes_id')
            ->select('pedidos.*' ,'clientes.*','tiendas.*')
            ->where('pedidos.created_at','like',"%{$date->toDateString()}%")
            ->where('pedidos.clientes_id','!=',1)
            ->get();

        $pdf = PDF::loadView('pdf/pedidos_completo', compact('pedidos'));
        $this->auditService->log(
            'pdf.pedidos_diario.generado',
            'pedidos',
            null,
            null,
            ['fecha' => $date->toDateString(), 'cantidad' => $pedidos->count()]
        );
        return $pdf->stream('prueba_1.pdf');  
        }
}
