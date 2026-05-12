<?php
namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Tienda;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\DetallePedido;
use App\Models\Pago;
use App\Models\PagoTransferencia;
use App\Services\Ventas\AbonoService;
use Illuminate\Http\Request;
use DB;

class HomeController extends Controller
{
    public function __construct(private readonly AbonoService $abonoService)
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $clientes= DB::table('clientes')->get();
        $tiendas = DB::table('tiendas')->orderBy('nombre_tienda')->get();
        $productos = DB::table('productos')
            ->select('id', 'descripcion_producto', 'stock_venta_producto', 'precio_venta_producto', 'imagen_producto')
            ->where('estado_producto', 1)
            ->orderBy('descripcion_producto')
            ->get();

        $variantesPorProducto = DB::table('colores_productos')
            ->leftJoin('colores', 'colores_productos.colores_id', '=', 'colores.id')
            ->whereNotNull('colores_productos.talla_por_color')
            ->where('colores_productos.talla_por_color', '!=', '')
            ->whereRaw('CAST(COALESCE(colores_productos.stock_por_color,0) AS SIGNED) > 0')
            ->orderBy('colores_productos.producto_id')
            ->orderByRaw('COALESCE(colores.nombre_color, "") asc')
            ->orderBy('colores_productos.talla_por_color')
            ->select([
                'colores_productos.producto_id',
                'colores_productos.colores_id',
                'colores_productos.talla_por_color',
                'colores_productos.stock_por_color',
                DB::raw('COALESCE(colores.nombre_color, CONCAT("Color #", colores_productos.colores_id)) as nombre_color'),
                DB::raw('COALESCE(colores.codigo_color, "#94a3b8") as codigo_color'),
            ])
            ->get()
            ->groupBy('producto_id')
            ->map(fn ($rows) => $rows->values())
            ->toArray();

        return view('home', compact(
            'clientes',
            'tiendas',
            'productos',
            'variantesPorProducto'
        ));
    }

    public function registrarVentaFisica(Request $request)
    {
        $validated = $request->validate([
            'descripcion' => 'nullable|string|max:255',
            'tienda_id' => 'required|exists:tiendas,id',
            'cliente_modo' => 'required|in:existente,nuevo',
            'clientes_id' => 'nullable|exists:clientes,id',
            'nombres_clientes' => 'nullable|string|max:120',
            'apellidos_clientes' => 'nullable|string|max:120',
            'cedula_clientes' => 'nullable|string|max:40',
            'telefono_clientes' => 'nullable|string|max:40',
            'ciudad_clientes' => 'nullable|string|max:80',
            'direccion_clientes' => 'nullable|string|max:255',
            'email_clientes' => 'nullable|email|max:120',
            'lineas_json' => 'required|string',
            'descuento_extra' => 'nullable|numeric|min:0',
            'recargo_extra' => 'nullable|numeric|min:0',
            'metodo_pago_pos' => 'required|in:efectivo,transferencia,paypal,payphone',
            'modalidad_venta' => 'required|in:contado,apartado',
            'abono_inicial' => 'nullable|numeric|min:0',
            'referencia_pago_pos' => 'nullable|string|max:120|required_if:metodo_pago_pos,transferencia',
        ]);

        if ($validated['cliente_modo'] === 'existente' && empty($validated['clientes_id'])) {
            return back()->withErrors(['clientes_id' => 'Selecciona un cliente existente.'])->withInput();
        }

        $clienteId = null;
        if ($validated['cliente_modo'] === 'existente') {
            $clienteId = (int) $validated['clientes_id'];
        } else {
            foreach (['nombres_clientes','apellidos_clientes','cedula_clientes','telefono_clientes'] as $field) {
                if (empty($validated[$field])) {
                    return back()->withErrors([$field => 'Este campo es obligatorio para cliente nuevo.'])->withInput();
                }
            }
            $cliente = Cliente::query()->firstOrCreate(
                ['cedula_clientes' => $validated['cedula_clientes']],
                [
                    'nombres_clientes' => $validated['nombres_clientes'],
                    'apellidos_clientes' => $validated['apellidos_clientes'],
                    'telefono_clientes' => $validated['telefono_clientes'],
                    'ciudad_clientes' => $validated['ciudad_clientes'] ?? '',
                    'direccion_clientes' => $validated['direccion_clientes'] ?? '',
                    'email_clientes' => $validated['email_clientes'] ?? null,
                    'estado_clientes' => 1,
                ]
            );
            if (!$cliente->wasRecentlyCreated) {
                $cliente->update([
                    'nombres_clientes' => $validated['nombres_clientes'],
                    'apellidos_clientes' => $validated['apellidos_clientes'],
                    'telefono_clientes' => $validated['telefono_clientes'],
                    'ciudad_clientes' => $validated['ciudad_clientes'] ?? $cliente->ciudad_clientes,
                    'direccion_clientes' => $validated['direccion_clientes'] ?? $cliente->direccion_clientes,
                    'email_clientes' => $validated['email_clientes'] ?? $cliente->email_clientes,
                ]);
            }
            $clienteId = (int) $cliente->id;
        }

        $lineasRaw = json_decode((string) $validated['lineas_json'], true);
        if (!is_array($lineasRaw) || empty($lineasRaw)) {
            return back()->withErrors(['lineas_json' => 'Agrega al menos un producto a la factura.'])->withInput();
        }

        DB::transaction(function () use ($validated, $lineasRaw, $clienteId) {
            $metodoPago = (string) $validated['metodo_pago_pos'];
            $modalidadVenta = (string) ($validated['modalidad_venta'] ?? 'contado');
            $esApartado = $modalidadVenta === 'apartado';
            $estadoPago = $esApartado
                ? 'PENDIENTE'
                : (in_array($metodoPago, ['paypal', 'payphone'], true) ? 'PENDIENTE' : 'APROBADO');
            $estadoPedido = $estadoPago === 'APROBADO' ? 'PAGADO' : 'PENDIENTE_PAGO';
            $estadoEnvio = $estadoPago === 'APROBADO' ? 'ENTREGADO' : 'SIN_ENVIO';

            $pedido = Pedido::query()->create([
                'codigo_pedido' => 'BF-POS-' . now()->format('YmdHis'),
                'clientes_id' => $clienteId,
                'tienda_id' => (int) $validated['tienda_id'],
                'descripcion' => $validated['descripcion'] ?: 'Venta fisica tienda',
                'subtotal_pedido' => 0,
                'descuentos_pedido' => 0,
                'total_pedido' => 0,
                'estado_pedidos' => 1,
                'estado_url' => $estadoPago === 'APROBADO' ? 'ENVIADO' : 'EN ESPERA',
                'estado_pedido' => $estadoPedido,
                'estado_pago' => $estadoPago,
                'estado_envio' => $estadoEnvio,
                'confirmado_at' => now(),
                'pagado_at' => $estadoPago === 'APROBADO' ? now() : null,
                'despachado_at' => $estadoPago === 'APROBADO' ? now() : null,
            ]);

            $subtotal = 0.0;
            $lineasProcesadas = 0;
            $totalesPorProducto = [];

            foreach ($lineasRaw as $linea) {
                $productoId = (int) ($linea['producto_id'] ?? 0);
                $colorId = (int) ($linea['colores_id'] ?? 0);
                $talla = trim((string) ($linea['talla_por_color'] ?? ''));
                $cantidad = (int) ($linea['cantidad'] ?? 0);
                if ($productoId < 1 || $colorId < 1 || $talla === '' || $cantidad < 1) {
                    continue;
                }

                $producto = Producto::query()->lockForUpdate()->findOrFail($productoId);
                $variante = DB::table('colores_productos')
                    ->where('producto_id', $productoId)
                    ->where('colores_id', $colorId)
                    ->where('talla_por_color', $talla)
                    ->lockForUpdate()
                    ->first();
                if (!$variante) {
                    throw new \RuntimeException("Variante no encontrada para {$producto->descripcion_producto}.");
                }
                if ((int) $variante->stock_por_color < $cantidad) {
                    throw new \RuntimeException("Stock insuficiente para {$producto->descripcion_producto} ({$talla}).");
                }

                DB::table('colores_productos')
                    ->where('producto_id', $productoId)
                    ->where('colores_id', $colorId)
                    ->where('talla_por_color', $talla)
                    ->update(['stock_por_color' => (int) $variante->stock_por_color - $cantidad]);

                $precio = (float) ($producto->precio_venta_producto ?? 0);
                $subtotalLinea = $precio * $cantidad;
                $subtotal += $subtotalLinea;
                $lineasProcesadas++;
                $totalesPorProducto[$productoId] = ($totalesPorProducto[$productoId] ?? 0) + $cantidad;

                DetallePedido::query()->create([
                    'cantidad_producto' => $cantidad,
                    'producto_id' => $productoId,
                    'pedido_id' => $pedido->id,
                    'id_color_producto' => $colorId,
                    'talla_por_color' => $talla,
                    'nombre_producto_snapshot' => $linea['producto_nombre'] ?? $producto->descripcion_producto,
                    'color_snapshot' => $linea['nombre_color'] ?? null,
                    'talla_snapshot' => $talla,
                    'precio_unitario_snapshot' => $precio,
                    'subtotal_linea' => $subtotalLinea,
                    'descuento_linea' => 0,
                    'impuesto_linea' => 0,
                    'estado_dtpedidos' => 1,
                ]);
            }

            foreach ($totalesPorProducto as $productoId => $cantidadTotal) {
                $productoActual = Producto::query()->lockForUpdate()->findOrFail($productoId);
                if ((int) $productoActual->stock_venta_producto < $cantidadTotal) {
                    throw new \RuntimeException("Stock total insuficiente para {$productoActual->descripcion_producto}.");
                }
                $productoActual->update([
                    'stock_venta_producto' => (int) $productoActual->stock_venta_producto - $cantidadTotal,
                ]);
            }

            if ($lineasProcesadas < 1) {
                throw new \RuntimeException('No hay lineas de producto validas para facturar.');
            }

            $descuento = round((float) ($validated['descuento_extra'] ?? 0), 2);
            $recargo = round((float) ($validated['recargo_extra'] ?? 0), 2);
            $subtotalConRecargo = round($subtotal + $recargo, 2);
            $total = round(max(0, $subtotalConRecargo - $descuento), 2);

            $pedido->update([
                'subtotal_pedido' => $subtotalConRecargo,
                'descuentos_pedido' => $descuento,
                'total_pedido' => $total,
            ]);

            if ($esApartado) {
                $abonoInicial = round((float) ($validated['abono_inicial'] ?? 0), 2);
                if ($abonoInicial <= 0) {
                    throw new \RuntimeException('Debes registrar un abono inicial para guardar un apartado.');
                }
                if ($abonoInicial > $total) {
                    throw new \RuntimeException('El abono inicial no puede superar el total de la venta.');
                }
            }

            $pago = Pago::query()->create([
                'pedido_id' => $pedido->id,
                'metodo' => $esApartado ? 'apartado' : $metodoPago,
                'estado' => $estadoPago,
                'monto' => $total,
                'moneda' => 'USD',
                'referencia_externa' => $validated['referencia_pago_pos'] ?? null,
                'metadata' => ['origen' => 'pos_home', 'provider_confirmed' => $estadoPago === 'APROBADO', 'modalidad_venta' => strtoupper($modalidadVenta)],
                'revisado_por' => auth()->id(),
                'revisado_at' => now(),
                'aprobado_at' => $estadoPago === 'APROBADO' ? now() : null,
            ]);

            if ($validated['metodo_pago_pos'] === 'transferencia') {
                PagoTransferencia::query()->create([
                    'pago_id' => $pago->id,
                    'numero_referencia' => $validated['referencia_pago_pos'] ?? null,
                    'fecha_transferencia' => now(),
                ]);
            }

            Venta::query()->updateOrCreate(
                ['pedido_id' => $pedido->id],
                [
                    'clientes_id' => $pedido->clientes_id,
                    'tienda_id' => $pedido->tienda_id,
                    'subtotal' => $subtotalConRecargo,
                    'descuento' => $descuento,
                    'total' => $total,
                    'estado_venta' => $estadoPago === 'APROBADO' ? 'PAGADO' : ($esApartado ? 'PENDIENTE_ABONO' : 'PENDIENTE'),
                    'modalidad_venta' => strtoupper($modalidadVenta),
                    'fecha_venta' => now(),
                    'fecha_proximo_abono' => $esApartado ? now()->addDays(7) : null,
                    'fecha_ultimo_abono' => null,
                ]
            );

            if ($esApartado) {
                $venta = Venta::query()->where('pedido_id', $pedido->id)->firstOrFail();
                $this->abonoService->registrarAbono(
                    $venta,
                    (float) $validated['abono_inicial'],
                    $metodoPago,
                    $validated['referencia_pago_pos'] ?? null,
                    null,
                    'Abono inicial de apartado (venta mostrador).',
                    auth()->id()
                );
            }
        });

        return redirect()->route('home')->with('success', 'Factura creada correctamente en una sola pantalla.');
    }
}
