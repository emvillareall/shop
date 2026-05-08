<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Tienda;
use App\Models\Venta;
use App\Models\Pago;
use App\Models\PagoTransferencia;
use App\Services\Auditoria\AuditService;
use App\Services\Inventario\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class PedidoController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly InventarioService $inventarioService
    ) {}

    public function index()
    {
        return view('pedido.index');
    }

    public function create()
    {
        $pedido = new Pedido();
        $clientes = DB::table('clientes')->get();
        $tienda_id = Tienda::select(DB::raw('nombre_tienda as nombre_tienda'), DB::raw('id as id'))
            ->pluck('nombre_tienda', 'id');

        $linkGenerado = session('link_generado');
        $linkPedidoId = session('link_pedido_id');
        $whatsappUrl = session('whatsapp_url');
        $modoEnvioLink = session('modo_envio_link');
        return view('pedido.create', compact('pedido', 'tienda_id', 'clientes', 'linkGenerado', 'linkPedidoId', 'whatsappUrl', 'modoEnvioLink'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_cliente' => 'required|in:existente,nuevo',
            'clientes_id' => 'nullable|exists:clientes,id',
            'telefono_nuevo' => 'nullable|string|max:30',
            'modo_envio_link' => 'nullable|in:whatsapp,copiar',
            'tienda_id' => 'required|exists:tiendas,id',
            'descripcion' => 'nullable|string|max:255',
            'subtotal_pedido' => 'nullable|numeric',
            'descuentos_pedido' => 'nullable|numeric',
            'estado_pedidos' => 'nullable',
            'estado_url' => 'nullable|string|max:40',
            'estado_pedido' => 'nullable|string|max:40',
            'estado_pago' => 'nullable|string|max:40',
            'estado_envio' => 'nullable|string|max:40',
            'codigo_pedido' => 'nullable|string|max:40',
            'confirmado_at' => 'nullable|date',
            'despachado_at' => 'nullable|date',
            'cancelado_at' => 'nullable|date',
        ]);
        $data = $validated;

        if (($data['tipo_cliente'] ?? 'existente') === 'existente' && empty($data['clientes_id'])) {
            return back()->withErrors(['clientes_id' => 'Selecciona un cliente existente.'])->withInput();
        }

        $clientePlaceholderId = (int) (Cliente::query()->where('id', 1)->value('id') ?: Cliente::query()->value('id'));
        if (($data['tipo_cliente'] ?? 'existente') === 'nuevo') {
            if (!$clientePlaceholderId) {
                return back()->withErrors(['clientes_id' => 'No hay cliente placeholder disponible para pedidos nuevos.'])->withInput();
            }
            $data['clientes_id'] = $clientePlaceholderId;
        }

        $data['estado_pedido'] = $data['estado_pedido'] ?? 'PENDIENTE_PAGO';
        $data['estado_pago'] = $data['estado_pago'] ?? 'SIN_PAGO';
        $data['estado_envio'] = $data['estado_envio'] ?? 'SIN_ENVIO';
        $data['codigo_pedido'] = $data['codigo_pedido'] ?? ('BF-ADM-' . now()->format('YmdHis'));
        $data['confirmado_at'] = $data['confirmado_at'] ?? now();

        if (($data['estado_envio'] ?? null) === 'ENVIADO') {
            $data['estado_url'] = 'ENVIADO';
            $data['despachado_at'] = $data['despachado_at'] ?? now();
        }
        if (($data['estado_pedido'] ?? null) === 'CANCELADO') {
            $data['estado_pedidos'] = 0;
            $data['cancelado_at'] = $data['cancelado_at'] ?? now();
        }
        unset($data['tipo_cliente'], $data['telefono_nuevo'], $data['modo_envio_link']);

        $pedido = Pedido::create($data);
        $this->syncVenta($pedido);

        if (($request->input('tipo_cliente') === 'nuevo')) {
            $signedUrl = URL::temporarySignedRoute(
                'event.subscribe',
                now()->addHours(6),
                ['id' => $pedido->id]
            );
            $telefono = preg_replace('/\D+/', '', (string) $request->input('telefono_nuevo', ''));
            if (str_starts_with($telefono, '09') && strlen($telefono) === 10) {
                $telefono = '593' . substr($telefono, 1);
            } elseif (str_starts_with($telefono, '9') && strlen($telefono) === 9) {
                $telefono = '593' . $telefono;
            }
            $whatsapp = null;
            if (str_starts_with($telefono, '593') && strlen($telefono) >= 11) {
                $msg = rawurlencode("Hola, te compartimos tu formulario de datos para completar el pedido {$pedido->codigo_pedido}:\n{$signedUrl}");
                $whatsapp = "https://wa.me/{$telefono}?text={$msg}";
            }

            $modo = (string) $request->input('modo_envio_link', 'copiar');
            return redirect()->route('pedidos.create')
                ->with('success', 'Pedido creado para cliente nuevo. Comparte el enlace para completar datos.')
                ->with('link_generado', $signedUrl)
                ->with('link_pedido_id', $pedido->id)
                ->with('whatsapp_url', $whatsapp)
                ->with('modo_envio_link', $modo);
        }

        return redirect()->route('pedidos.index')
            ->with('success', 'Pedido creado correctamente.');
    }

    public function show($id)
    {
        $pedido = Pedido::find($id);
        $signedUrl = null;
        $whatsappUrl = null;
        if ($pedido) {
            $signedUrl = URL::temporarySignedRoute('event.subscribe', now()->addHours(6), ['id' => $pedido->id]);
            $cliente = Cliente::query()->find($pedido->clientes_id);
            $telefono = preg_replace('/\D+/', '', (string) ($cliente->telefono_clientes ?? ''));
            if (str_starts_with($telefono, '09') && strlen($telefono) === 10) {
                $telefono = '593' . substr($telefono, 1);
            } elseif (str_starts_with($telefono, '9') && strlen($telefono) === 9) {
                $telefono = '593' . $telefono;
            }
            if (str_starts_with($telefono, '593') && strlen($telefono) >= 11) {
                $msg = rawurlencode("Hola, te compartimos tu formulario de datos para completar el pedido {$pedido->codigo_pedido}:\n{$signedUrl}");
                $whatsappUrl = "https://wa.me/{$telefono}?text={$msg}";
            }
        }

        return view('pedido.show', compact('pedido', 'signedUrl', 'whatsappUrl'));
    }

    public function edit($id)
    {
        $pedido = Pedido::find($id);
        $clientes = DB::table('clientes')->where('id', $pedido->clientes_id)->get();
        $tienda_id = Tienda::select(DB::raw('nombre_tienda as nombre_tienda'), DB::raw('id as id'))
            ->pluck('nombre_tienda', 'id');
        return view('pedido.edit', compact('pedido', 'tienda_id', 'clientes'));
    }

    public function update(Request $request, Pedido $pedido)
    {
        $validated = $request->validate([
            'clientes_id' => 'required|exists:clientes,id',
            'tienda_id' => 'required|exists:tiendas,id',
            'descripcion' => 'nullable|string|max:255',
            'subtotal_pedido' => 'nullable|numeric',
            'descuentos_pedido' => 'nullable|numeric',
            'estado_pedidos' => 'nullable',
            'estado_url' => 'nullable|string|max:40',
            'estado_pedido' => 'nullable|string|max:40',
            'estado_pago' => 'nullable|string|max:40',
            'estado_envio' => 'nullable|string|max:40',
            'codigo_pedido' => 'nullable|string|max:40',
        ]);
        $data = $validated;

        $data['estado_pedido'] = $data['estado_pedido'] ?? ($pedido->estado_pedido ?? 'PENDIENTE_PAGO');
        $data['estado_pago'] = $data['estado_pago'] ?? ($pedido->estado_pago ?? 'SIN_PAGO');
        $data['estado_envio'] = $data['estado_envio'] ?? ($pedido->estado_envio ?? 'SIN_ENVIO');

        if (($data['estado_envio'] ?? null) === 'ENVIADO') {
            $data['estado_url'] = 'ENVIADO';
            $data['despachado_at'] = $pedido->despachado_at ?? now();
        } elseif (($pedido->estado_url ?? null) === 'ENVIADO') {
            $data['estado_url'] = 'EN ESPERA';
            $data['despachado_at'] = null;
        }

        if (($data['estado_pedido'] ?? null) === 'CANCELADO') {
            $data['estado_pedidos'] = 0;
            $data['cancelado_at'] = $pedido->cancelado_at ?? now();
        } else {
            $data['estado_pedidos'] = 1;
            $data['cancelado_at'] = null;
        }

        if (($data['estado_pago'] ?? null) === 'APROBADO') {
            $data['pagado_at'] = $pedido->pagado_at ?? now();
        } elseif (($data['estado_pago'] ?? null) === 'RECHAZADO') {
            $data['pagado_at'] = null;
        }

        $pedido->update($data);
        $pedido->total_pedido = $pedido->subtotal_pedido - $pedido->descuentos_pedido;
        $pedido->save();
        $this->syncVenta($pedido);
        return redirect()->route('pedidos.index')
            ->with('success', 'Pedido updated successfully');
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $beforePedido = DB::table('pedidos')->where('id', $id)->first();
            if (!$beforePedido) {
                DB::rollBack();
                return redirect()->route('pedidos.index')->with('danger', 'Pedido no encontrado.');
            }
            if ((int) ($beforePedido->estado_pedidos ?? 1) === 0) {
                DB::rollBack();
                return redirect()->route('pedidos.index')->with('success', 'El pedido ya estaba cancelado.');
            }

            $this->inventarioService->revertirSalidaPedido((int) $id);

            $pedidoUpdate = ['estado_pedidos' => 0];
            if (Schema::hasColumn('pedidos', 'estado_pedido')) {
                $pedidoUpdate['estado_pedido'] = 'CANCELADO';
            }
            if (Schema::hasColumn('pedidos', 'cancelado_at')) {
                $pedidoUpdate['cancelado_at'] = now();
            }

            DB::table('pedidos')->where('id', $id)->update($pedidoUpdate);
            DB::table('detalle_pedidos')->where('pedido_id', $id)->update(['estado_dtpedidos' => 0]);
            DB::commit();

            $afterPedido = DB::table('pedidos')->where('id', $id)->first();
            $this->auditService->log(
                'pedido.cancelado',
                'pedidos',
                $id,
                $beforePedido ? (array) $beforePedido : null,
                $afterPedido ? (array) $afterPedido : null
            );

            return redirect()->route('pedidos.index')
                ->with('success', 'Pedido cancelado y stock revertido correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('pedidos.index')
                ->with('danger', 'Error al cancelar el pedido: ' . $e->getMessage());
        }
    }

    public function cambiar_de_estado(Request $request, $id)
    {
        $beforePedido = DB::table('pedidos')->where('id', $id)->first();
        if (!$beforePedido) {
            return redirect()->route('pedidos.index')->with('danger', 'Pedido no encontrado.');
        }

        $estadoPago = (string) ($beforePedido->estado_pago ?? 'SIN_PAGO');
        if ($estadoPago !== 'APROBADO') {
            return redirect()->route('pedidos.index')->with('danger', 'No se puede enviar: el pago no esta confirmado.');
        }

        $detallesCount = DB::table('detalle_pedidos')
            ->where('pedido_id', $id)
            ->where(function ($q) {
                $q->whereNull('estado_dtpedidos')->orWhere('estado_dtpedidos', 1);
            })
            ->count();
        if ($detallesCount <= 0) {
            return redirect()->route('pedidos.index')->with('danger', 'No se puede enviar: el pedido no tiene productos asignados.');
        }

        $updateData = ['estado_url' => 'ENVIADO'];
        if (Schema::hasColumn('pedidos', 'estado_pedido')) {
            $updateData['estado_pedido'] = 'DESPACHADO';
        }
        if (Schema::hasColumn('pedidos', 'estado_envio')) {
            $updateData['estado_envio'] = 'ENVIADO';
        }
        if (Schema::hasColumn('pedidos', 'despachado_at')) {
            $updateData['despachado_at'] = now();
        }

        DB::table('pedidos')->where('id', $id)->update($updateData);
        $pedidoModel = Pedido::query()->find($id);
        if ($pedidoModel) {
            $this->syncVenta($pedidoModel);
        }

        $afterPedido = DB::table('pedidos')->where('id', $id)->first();
        $this->auditService->log(
            'pedido.despachado',
            'pedidos',
            $id,
            $beforePedido ? (array) $beforePedido : null,
            $afterPedido ? (array) $afterPedido : null
        );

        $returnTo = $request->input('return_to');
        if ($returnTo && Str::startsWith($returnTo, url('/'))) {
            return redirect()->to($returnTo)->with('success', 'Pedido marcado como ENVIADO.');
        }

        return redirect()->route('pedidos.index')->with('success', 'Pedido marcado como ENVIADO.');
    }

    private function syncVenta(Pedido $pedido): void
    {
        Venta::query()->updateOrCreate(
            ['pedido_id' => $pedido->id],
            [
                'clientes_id' => $pedido->clientes_id,
                'tienda_id' => $pedido->tienda_id,
                'subtotal' => (float) ($pedido->subtotal_pedido ?? 0),
                'descuento' => (float) ($pedido->descuentos_pedido ?? 0),
                'total' => (float) (($pedido->subtotal_pedido ?? 0) - ($pedido->descuentos_pedido ?? 0)),
                'estado_venta' => (string) ($pedido->estado_pedido ?? 'PENDIENTE_PAGO'),
                'fecha_venta' => $pedido->confirmado_at ?: now(),
            ]
        );
    }

    public function confirmarPagoSocial(Request $request, Pedido $pedido)
    {
        $validated = $request->validate([
            'numero_referencia' => 'nullable|string|max:120',
            'comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'observacion' => 'nullable|string|max:500',
        ]);

        $esSocial = str_starts_with((string) ($pedido->codigo_pedido ?? ''), 'BF-ADM-')
            || !str_contains(strtolower((string) ($pedido->descripcion ?? '')), 'en linea');
        if (!$esSocial) {
            return back()->with('danger', 'Este pedido corresponde a ecommerce y su pago se valida desde el modulo Pagos.');
        }

        if (($pedido->estado_pago ?? 'SIN_PAGO') === 'APROBADO') {
            return back()->with('success', 'El pago de este pedido ya estaba confirmado.');
        }

        $comprobantePath = null;
        if ($request->hasFile('comprobante')) {
            $comprobantePath = $request->file('comprobante')->store('comprobantes/social', 'public');
        }

        DB::transaction(function () use ($pedido, $validated, $comprobantePath) {
            $total = (float) (($pedido->subtotal_pedido ?? 0) - ($pedido->descuentos_pedido ?? 0));

            $pago = Pago::query()->updateOrCreate(
                ['pedido_id' => $pedido->id, 'metodo' => 'transferencia'],
                [
                    'estado' => 'APROBADO',
                    'monto' => $total,
                    'moneda' => 'USD',
                    'referencia_externa' => $validated['numero_referencia'] ?? null,
                    'comprobante_path' => $comprobantePath,
                    'observacion' => $validated['observacion'] ?? null,
                    'metadata' => ['origen' => 'admin_social', 'provider_confirmed' => true],
                    'revisado_por' => auth()->id(),
                    'revisado_at' => now(),
                    'aprobado_at' => now(),
                    'rechazado_at' => null,
                ]
            );

            PagoTransferencia::query()->updateOrCreate(
                ['pago_id' => $pago->id],
                [
                    'numero_referencia' => $validated['numero_referencia'] ?? null,
                    'fecha_transferencia' => now(),
                ]
            );

            $pedido->update([
                'estado_pago' => 'APROBADO',
                'estado_pedido' => 'PAGADO',
                'pagado_at' => now(),
            ]);

            $this->syncVenta($pedido->fresh());
        });

        return back()->with('success', 'Pago confirmado correctamente para el pedido.');
    }
}
