<?php

namespace App\Livewire\Ecommerce;

use App\Models\Cliente;
use App\Models\DetallePedido;
use App\Models\Pago;
use App\Models\PagoPayphone;
use App\Models\PagoPaypal;
use App\Models\PagoTransferencia;
use App\Models\Pedido;
use App\Services\Inventario\InventarioService;
use App\Services\Inventario\StockReservaService;
use App\Services\Pagos\PaymentGatewayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Checkout extends Component
{
    use WithFileUploads;

    protected InventarioService $inventarioService;
    protected StockReservaService $stockReservaService;
    protected PaymentGatewayService $paymentGatewayService;

    public array $items = [];
    public string $cedula = '';
    public ?int $cliente_id = null;
    public array $cliente = [
        'nombres_clientes' => '',
        'apellidos_clientes' => '',
        'telefono_clientes' => '',
        'ciudad_clientes' => '',
        'direccion_clientes' => '',
        'email_clientes' => '',
    ];
    public string $metodo_pago = 'transferencia';
    public ?string $referencia_transferencia = null;
    public $comprobante_transferencia = null;
    public int $secondsRemaining = 0;
    public bool $paypalDisponible = false;
    public bool $payphoneDisponible = false;

    protected array $validationAttributes = [
        'cedula' => 'cedula / identificacion',
        'cliente.nombres_clientes' => 'nombres',
        'cliente.apellidos_clientes' => 'apellidos',
        'cliente.telefono_clientes' => 'telefono',
        'cliente.ciudad_clientes' => 'ciudad',
        'cliente.direccion_clientes' => 'direccion',
        'cliente.email_clientes' => 'correo electronico',
        'metodo_pago' => 'metodo de pago',
        'referencia_transferencia' => 'referencia de transferencia',
        'comprobante_transferencia' => 'comprobante de transferencia',
    ];

    protected array $messages = [
        'required' => 'El campo :attribute es obligatorio.',
        'email' => 'El campo :attribute debe ser un correo valido.',
        'max' => 'El campo :attribute no puede superar :max caracteres.',
        'min' => 'El campo :attribute debe tener al menos :min caracteres.',
        'in' => 'El campo :attribute seleccionado no es valido.',
        'file' => 'El campo :attribute debe ser un archivo.',
        'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    ];

    public function boot(InventarioService $inventarioService, StockReservaService $stockReservaService, PaymentGatewayService $paymentGatewayService): void
    {
        $this->inventarioService = $inventarioService;
        $this->stockReservaService = $stockReservaService;
        $this->paymentGatewayService = $paymentGatewayService;
    }

    protected function rules(): array
    {
        return [
            'cedula' => 'required|string|min:5|max:40',
            'cliente.nombres_clientes' => 'required|string|max:120',
            'cliente.apellidos_clientes' => 'required|string|max:120',
            'cliente.telefono_clientes' => 'required|string|max:40',
            'cliente.ciudad_clientes' => 'required|string|max:80',
            'cliente.direccion_clientes' => 'required|string|max:255',
            'cliente.email_clientes' => 'required|email|max:120',
        ];
    }

    protected function rulesPago(): array
    {
        return [
            'metodo_pago' => 'required|in:transferencia,paypal,payphone',
            'referencia_transferencia' => 'nullable|string|max:120',
            'comprobante_transferencia' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    private function resolverCliente(): void
    {
        $this->validate($this->rules());

        $registro = Cliente::query()
            ->where('cedula_clientes', $this->cedula)
            ->orWhere('email_clientes', $this->cliente['email_clientes'])
            ->orWhere('telefono_clientes', $this->cliente['telefono_clientes'])
            ->first();

        if ($registro) {
            $registro->update([
                'cedula_clientes' => $this->cedula,
                'nombres_clientes' => $this->cliente['nombres_clientes'],
                'apellidos_clientes' => $this->cliente['apellidos_clientes'],
                'telefono_clientes' => $this->cliente['telefono_clientes'],
                'ciudad_clientes' => $this->cliente['ciudad_clientes'],
                'direccion_clientes' => $this->cliente['direccion_clientes'],
                'email_clientes' => $this->cliente['email_clientes'],
            ]);
            $this->cliente_id = (int) $registro->id;
            return;
        }

        $nuevo = Cliente::query()->create([
            'cedula_clientes' => $this->cedula,
            'nombres_clientes' => $this->cliente['nombres_clientes'],
            'apellidos_clientes' => $this->cliente['apellidos_clientes'],
            'telefono_clientes' => $this->cliente['telefono_clientes'],
            'ciudad_clientes' => $this->cliente['ciudad_clientes'],
            'direccion_clientes' => $this->cliente['direccion_clientes'],
            'email_clientes' => $this->cliente['email_clientes'],
            'estado_clientes' => 1,
        ]);

        $this->cliente_id = (int) $nuevo->id;
    }

    public function confirmarPedido()
    {
        $items = session()->get('carrito', []);
        if (empty($items)) {
            $this->addError('cliente_id', 'El carrito esta vacio.');
            return null;
        }

        try {
            $this->stockReservaService->assertSessionActive(session()->getId());
            $this->stockReservaService->syncFromCart(session()->getId(), $items);
            $this->refreshReservationClock();
        } catch (\Throwable $e) {
            session()->forget('carrito');
            $this->items = [];
            $this->addError('cliente_id', $e->getMessage());
            return null;
        }

        $this->resolverCliente();
        $this->validate($this->rulesPago());

        if ($this->metodo_pago === 'paypal' && !$this->paypalDisponible) {
            $this->addError('metodo_pago', 'PayPal no está disponible en este momento.');
            return null;
        }
        if ($this->metodo_pago === 'payphone' && !$this->payphoneDisponible) {
            $this->addError('metodo_pago', 'Payphone no está disponible en este momento.');
            return null;
        }
        if ($this->metodo_pago === 'transferencia' && !$this->comprobante_transferencia) {
            $this->addError('comprobante_transferencia', 'Debes subir el comprobante de transferencia.');
            return null;
        }

        $comprobantePath = null;
        if ($this->metodo_pago === 'transferencia' && $this->comprobante_transferencia) {
            $comprobantePath = $this->comprobante_transferencia->store('comprobantes/transferencias', 'public');
        }

        [$pedido, $pago] = DB::transaction(function () use ($items, $comprobantePath) {
            $estadoPedido = $this->metodo_pago === 'transferencia' ? 'PAGO_EN_REVISION' : 'PENDIENTE_PAGO';
            $estadoPago = $this->metodo_pago === 'transferencia' ? 'EN_REVISION' : 'PENDIENTE';

            $pedido = Pedido::create([
                'codigo_pedido' => 'BF-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'clientes_id' => $this->cliente_id,
                'tienda_id' => 1,
                'descripcion' => 'Pedido en linea',
                'subtotal_pedido' => 0,
                'descuentos_pedido' => 0,
                'estado_pedidos' => 1,
                'estado_url' => 'EN ESPERA',
                'estado_pedido' => $estadoPedido,
                'estado_pago' => $estadoPago,
                'estado_envio' => 'SIN_ENVIO',
                'confirmado_at' => now(),
            ]);

            $total = 0;
            foreach ($items as $item) {
                $this->inventarioService->descontarVariante(
                    (int) $item['producto_id'],
                    (int) $item['color_id'],
                    (string) $item['talla'],
                    (int) $item['cantidad'],
                    (int) $pedido->id
                );

                DetallePedido::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $item['producto_id'],
                    'cantidad_producto' => $item['cantidad'],
                    'id_color_producto' => $item['color_id'],
                    'talla_por_color' => $item['talla'],
                    'nombre_producto_snapshot' => $item['descripcion'] ?? null,
                    'color_snapshot' => $item['color'] ?? null,
                    'talla_snapshot' => $item['talla'] ?? null,
                    'precio_unitario_snapshot' => (float) $item['precio'],
                    'subtotal_linea' => ((float) $item['precio']) * ((int) $item['cantidad']),
                    'descuento_linea' => 0,
                    'impuesto_linea' => 0,
                    'estado_dtpedidos' => 1,
                ]);

                $total += ((float) $item['precio']) * ((int) $item['cantidad']);
            }

            $pedido->update([
                'subtotal_pedido' => $total,
                'total_pedido' => $total,
            ]);

            $pago = Pago::create([
                'pedido_id' => $pedido->id,
                'metodo' => $this->metodo_pago,
                'estado' => $estadoPago,
                'monto' => $total,
                'moneda' => 'USD',
                'referencia_externa' => $this->referencia_transferencia,
                'comprobante_path' => $comprobantePath,
                'metadata' => ['origen' => 'checkout_shop'],
            ]);

            if ($this->metodo_pago === 'transferencia') {
                PagoTransferencia::create([
                    'pago_id' => $pago->id,
                    'numero_referencia' => $this->referencia_transferencia,
                    'fecha_transferencia' => now(),
                ]);
            } elseif ($this->metodo_pago === 'paypal') {
                PagoPaypal::create([
                    'pago_id' => $pago->id,
                    'paypal_status' => 'PENDIENTE',
                ]);
            } elseif ($this->metodo_pago === 'payphone') {
                PagoPayphone::create([
                    'pago_id' => $pago->id,
                    'payphone_status' => 'PENDIENTE',
                ]);
            }

            $this->stockReservaService->consumeSessionReservations(session()->getId());
            session()->forget('carrito');

            return [$pedido, $pago];
        });

        if ($this->metodo_pago === 'paypal') {
            try {
                $order = $this->paymentGatewayService->createPaypalOrder(
                    $pago,
                    URL::temporarySignedRoute('payments.return', now()->addMinutes(30), ['gateway' => 'paypal', 'pago' => $pago->id]),
                    URL::temporarySignedRoute('payments.cancel', now()->addMinutes(30), ['gateway' => 'paypal', 'pago' => $pago->id])
                );

                $pago->update([
                    'referencia_externa' => $order['provider_order_id'] ?? $pago->referencia_externa,
                    'metadata' => array_merge((array) ($pago->metadata ?? []), ['paypal_order' => $order['raw'] ?? []]),
                ]);
                PagoPaypal::query()->where('pago_id', $pago->id)->update([
                    'paypal_order_id' => $order['provider_order_id'] ?? null,
                    'paypal_status' => 'CREATED',
                ]);

                if (!empty($order['redirect_url'])) {
                    return redirect()->away($order['redirect_url']);
                }
            } catch (\Throwable $e) {
                Log::error('paypal_create_order_failed', ['pago_id' => $pago->id, 'error' => $e->getMessage()]);
                $pago->update([
                    'estado' => 'EN_REVISION',
                    'observacion' => 'Error al iniciar PayPal: ' . $e->getMessage(),
                ]);
                return redirect()->route('ecommerce.pedido.confirmado', $pedido->id)
                    ->with('error', 'No se pudo iniciar PayPal. Pedido creado en revisión.');
            }
        }

        if ($this->metodo_pago === 'payphone') {
            try {
                $sale = $this->paymentGatewayService->createPayphoneCheckout(
                    $pago,
                    URL::temporarySignedRoute('payments.return', now()->addMinutes(30), ['gateway' => 'payphone', 'pago' => $pago->id])
                );

                $pago->update([
                    'referencia_externa' => $sale['provider_order_id'] ?? $pago->referencia_externa,
                    'metadata' => array_merge((array) ($pago->metadata ?? []), ['payphone_sale' => $sale['raw'] ?? []]),
                ]);
                PagoPayphone::query()->where('pago_id', $pago->id)->update([
                    'transaction_id' => $sale['provider_order_id'] ?? null,
                    'payphone_status' => 'CREATED',
                ]);

                if (!empty($sale['redirect_url'])) {
                    return redirect()->away($sale['redirect_url']);
                }
            } catch (\Throwable $e) {
                Log::error('payphone_create_sale_failed', ['pago_id' => $pago->id, 'error' => $e->getMessage()]);
                $pago->update([
                    'estado' => 'EN_REVISION',
                    'observacion' => 'Error al iniciar Payphone: ' . $e->getMessage(),
                ]);
                return redirect()->route('ecommerce.pedido.confirmado', $pedido->id)
                    ->with('error', 'No se pudo iniciar Payphone. Pedido creado en revisión.');
            }
        }

        return redirect()->route('ecommerce.pedido.confirmado', $pedido->id);
    }

    public function getItemsProperty(): array
    {
        return session()->get('carrito', []);
    }

    public function getTotalProperty(): float
    {
        return collect($this->items)->sum(
            fn ($item) => ((float) ($item['precio'] ?? 0)) * ((int) ($item['cantidad'] ?? 0))
        );
    }

    public function mount(): void
    {
        $fakeMode = (bool) config('payments.fake_mode', false);
        $this->paypalDisponible = $fakeMode || (bool) (config('payments.paypal.client_id') && config('payments.paypal.client_secret'));
        $this->payphoneDisponible = $fakeMode || (bool) (config('payments.payphone.token') && config('payments.payphone.store_id'));

        if (!$this->paypalDisponible && $this->metodo_pago === 'paypal') {
            $this->metodo_pago = 'transferencia';
        }
        if (!$this->payphoneDisponible && $this->metodo_pago === 'payphone') {
            $this->metodo_pago = 'transferencia';
        }

        $this->items = session()->get('carrito', []);
        if (!empty($this->items)) {
            try {
                $this->stockReservaService->syncFromCart(session()->getId(), $this->items);
            } catch (\Throwable) {
                session()->forget('carrito');
                $this->items = [];
            }
        }
        $this->refreshReservationClock();
    }

    public function tickReserva(): void
    {
        $this->refreshReservationClock();
        if ($this->secondsRemaining === 0 && !empty($this->items)) {
            $this->stockReservaService->releaseSession(session()->getId());
            session()->forget('carrito');
            $this->items = [];
            $this->addError('cliente_id', 'La reserva expiró. Vuelve a cargar tu carrito.');
        }
    }

    private function refreshReservationClock(): void
    {
        $this->secondsRemaining = $this->stockReservaService->secondsRemaining();
    }

    public function render()
    {
        return view('livewire.ecommerce.checkout');
    }
}
