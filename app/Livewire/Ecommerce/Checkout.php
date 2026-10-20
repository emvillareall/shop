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
use App\Services\Parametros\GlobalParameterService;
use App\Services\Pagos\PaymentGatewayService;
use App\Services\Pagos\PaymentConfigService;
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
    protected PaymentConfigService $paymentConfigService;
    protected GlobalParameterService $globalParameterService;

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
    public float $ivaPercent = 0;
    public float $payphoneFeePercent = 0;

    protected array $validationAttributes = [
        'cedula' => 'cédula / identificación',
        'cliente.nombres_clientes' => 'nombres',
        'cliente.apellidos_clientes' => 'apellidos',
        'cliente.telefono_clientes' => 'teléfono',
        'cliente.ciudad_clientes' => 'ciudad',
        'cliente.direccion_clientes' => 'dirección',
        'cliente.email_clientes' => 'correo electrónico',
        'metodo_pago' => 'método de pago',
        'referencia_transferencia' => 'referencia de transferencia',
        'comprobante_transferencia' => 'comprobante de transferencia',
    ];

    protected array $messages = [
        'required' => 'El campo :attribute es obligatorio.',
        'email' => 'Ingresa un correo electrónico válido, por ejemplo nombre@dominio.com.',
        'max' => 'El campo :attribute no puede superar :max caracteres.',
        'in' => 'El campo :attribute seleccionado no es válido.',
        'file' => 'El campo :attribute debe ser un archivo.',
        'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
        'cedula.required' => 'La cédula o identificación es obligatoria.',
        'cliente.telefono_clientes.required' => 'El teléfono de contacto es obligatorio.',
        'cliente.nombres_clientes.required' => 'Los nombres son obligatorios.',
        'cliente.apellidos_clientes.required' => 'Los apellidos son obligatorios.',
        'cliente.ciudad_clientes.required' => 'La ciudad de destino es obligatoria.',
        'cliente.direccion_clientes.required' => 'La dirección de entrega es obligatoria.',
        'cliente.email_clientes.required' => 'El correo electrónico es obligatorio.',
    ];

    public function boot(
        InventarioService $inventarioService,
        StockReservaService $stockReservaService,
        PaymentGatewayService $paymentGatewayService,
        PaymentConfigService $paymentConfigService,
        GlobalParameterService $globalParameterService
    ): void
    {
        $this->inventarioService = $inventarioService;
        $this->stockReservaService = $stockReservaService;
        $this->paymentGatewayService = $paymentGatewayService;
        $this->paymentConfigService = $paymentConfigService;
        $this->globalParameterService = $globalParameterService;
    }

    protected function rules(): array
    {
        return [
            'cedula' => [
                'required', 'string', 'max:40',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && $value !== '' && !$this->identificacionValida($value)) {
                        $fail('Ingresa una cédula ecuatoriana válida, un RUC de 13 dígitos con formato válido o un pasaporte de 5 a 20 caracteres.');
                    }
                },
            ],
            'cliente.nombres_clientes' => [
                'required', 'string', 'max:120',
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validarNombre($value, $fail, 'Los nombres deben tener al menos 2 caracteres y contener solo letras, espacios o separadores habituales.'),
            ],
            'cliente.apellidos_clientes' => [
                'required', 'string', 'max:120',
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validarNombre($value, $fail, 'Los apellidos deben tener al menos 2 caracteres y contener solo letras, espacios o separadores habituales.'),
            ],
            'cliente.telefono_clientes' => [
                'required', 'string', 'max:40',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && $value !== '' && !$this->telefonoMovilEcuadorValido($value)) {
                        $fail('Ingresa un celular ecuatoriano válido de 10 dígitos que empiece con 09. También puedes usar +593 9xxxxxxxx.');
                    }
                },
            ],
            'cliente.ciudad_clientes' => [
                'required', 'string', 'max:80',
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validarCiudad($value, $fail),
            ],
            'cliente.direccion_clientes' => [
                'required', 'string', 'max:255',
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validarDireccion($value, $fail),
            ],
            'cliente.email_clientes' => 'required|string|email:rfc|max:120',
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
        $this->normalizarDatosCliente();
        $datosValidados = $this->validate($this->rules());
        $clienteData = [
            'cedula_clientes' => $datosValidados['cedula'],
            'nombres_clientes' => $datosValidados['cliente']['nombres_clientes'],
            'apellidos_clientes' => $datosValidados['cliente']['apellidos_clientes'],
            'telefono_clientes' => $datosValidados['cliente']['telefono_clientes'],
            'ciudad_clientes' => $datosValidados['cliente']['ciudad_clientes'],
            'direccion_clientes' => $datosValidados['cliente']['direccion_clientes'],
            'email_clientes' => $datosValidados['cliente']['email_clientes'],
        ];

        $registro = Cliente::query()
            ->where('cedula_clientes', $clienteData['cedula_clientes'])
            ->orWhere('email_clientes', $clienteData['email_clientes'])
            ->orWhere('telefono_clientes', $clienteData['telefono_clientes'])
            ->first();

        if ($registro) {
            $registro->update($clienteData);
            $this->cliente_id = (int) $registro->id;
            return;
        }

        $nuevo = Cliente::query()->create($clienteData + ['estado_clientes' => 1]);

        $this->cliente_id = (int) $nuevo->id;
    }

    private function normalizarDatosCliente(): void
    {
        if (is_string($this->cedula)) {
            $this->cedula = $this->normalizarEspacios($this->cedula);
        }

        foreach (['nombres_clientes', 'apellidos_clientes', 'ciudad_clientes', 'direccion_clientes'] as $campo) {
            if (isset($this->cliente[$campo]) && is_string($this->cliente[$campo])) {
                $this->cliente[$campo] = $this->normalizarEspacios($this->cliente[$campo]);
            }
        }

        if (isset($this->cliente['telefono_clientes']) && is_string($this->cliente['telefono_clientes'])) {
            $telefono = preg_replace('/[\s()-]+/u', '', trim($this->cliente['telefono_clientes']));
            $telefono = is_string($telefono) ? $telefono : trim($this->cliente['telefono_clientes']);
            if (str_starts_with($telefono, '+5939') && strlen($telefono) === 13) {
                $telefono = '0' . substr($telefono, 4);
            } elseif (str_starts_with($telefono, '5939') && strlen($telefono) === 12) {
                $telefono = '0' . substr($telefono, 3);
            }
            $this->cliente['telefono_clientes'] = $telefono;
        }

        if (isset($this->cliente['email_clientes']) && is_string($this->cliente['email_clientes'])) {
            $this->cliente['email_clientes'] = Str::lower(trim($this->cliente['email_clientes']));
        }
    }

    private function normalizarEspacios(string $value): string
    {
        $value = preg_replace('/^\s+|\s+$/u', '', $value) ?? trim($value);
        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    private function identificacionValida(string $value): bool
    {
        if (preg_match('/^(.)\1+$/u', $value) || $this->esSecuenciaNumerica($value)) {
            return false;
        }

        if (preg_match('/^\d{10}$/D', $value)) {
            return $this->cedulaEcuatorianaValida($value);
        }

        if (preg_match('/^\d{13}$/D', $value)) {
            $provincia = (int) substr($value, 0, 2);
            $tipo = (int) $value[2];
            if ($provincia < 1 || $provincia > 24 || !($tipo <= 5 || in_array($tipo, [6, 9], true)) || substr($value, 10, 3) === '000') {
                return false;
            }

            return $tipo > 5 || $this->cedulaEcuatorianaValida(substr($value, 0, 10));
        }

        return (bool) preg_match('/^[A-Za-z0-9-]{5,20}$/D', $value);
    }

    private function cedulaEcuatorianaValida(string $value): bool
    {
        if (!preg_match('/^\d{10}$/D', $value)) {
            return false;
        }

        $provincia = (int) substr($value, 0, 2);
        $tercerDigito = (int) $value[2];
        if ($provincia < 1 || $provincia > 24 || $tercerDigito > 5) {
            return false;
        }

        $suma = 0;
        for ($indice = 0; $indice < 9; $indice++) {
            $digito = (int) $value[$indice];
            if ($indice % 2 === 0) {
                $digito *= 2;
                if ($digito > 9) {
                    $digito -= 9;
                }
            }
            $suma += $digito;
        }

        $verificador = (10 - ($suma % 10)) % 10;
        return $verificador === (int) $value[9];
    }

    private function esSecuenciaNumerica(string $value): bool
    {
        if (!ctype_digit($value) || strlen($value) < 5) {
            return false;
        }

        $paso = (int) $value[1] - (int) $value[0];
        if (abs($paso) !== 1) {
            return false;
        }

        for ($indice = 2, $longitud = strlen($value); $indice < $longitud; $indice++) {
            if ((int) $value[$indice] - (int) $value[$indice - 1] !== $paso) {
                return false;
            }
        }

        return true;
    }

    private function telefonoMovilEcuadorValido(string $value): bool
    {
        if (!preg_match('/^09\d{8}$/D', $value)) {
            return false;
        }

        $abonado = substr($value, 2);
        return !preg_match('/(.)\1{5,}/D', $abonado) && !$this->esSecuenciaNumerica($abonado);
    }

    private function validarNombre(mixed $value, \Closure $fail, string $message): void
    {
        if (!is_string($value) || $value === '') {
            return;
        }

        if (mb_strlen($value) < 2 || !preg_match('/^[\p{L}\p{M}]+(?:[ \x27\x{2019}.-][\p{L}\p{M}]+)*$/u', $value)) {
            $fail($message);
        }
    }

    private function validarCiudad(mixed $value, \Closure $fail): void
    {
        if (!is_string($value) || $value === '') {
            return;
        }

        if (mb_strlen($value) < 2 || !preg_match('/^[\p{L}\p{M}]+(?:[ \x27\x{2019}.-][\p{L}\p{M}]+)*$/u', $value)) {
            $fail('Ingresa el nombre de la ciudad o cantón de destino usando letras.');
        }
    }

    private function validarDireccion(mixed $value, \Closure $fail): void
    {
        if (!is_string($value) || $value === '') {
            return;
        }

        preg_match_all('/[\p{L}\p{N}]+/u', $value, $palabras);
        if (
            mb_strlen($value) < 20
            || count($palabras[0] ?? []) < 4
            || !preg_match('/\p{L}/u', $value)
            || !preg_match('/^[\p{L}\p{M}\p{N}\s.,#\x27°ºª\/()\-]+$/u', $value)
        ) {
            $fail('Completa una dirección de al menos 20 caracteres con calle principal, secundaria, sector y referencia. Usa letras, números y signos habituales de una dirección.');
        }
    }

    public function confirmarPedido()
    {
        if (!$this->reconcileCart(true)) {
            return null;
        }

        $items = $this->items;
        if (empty($items)) {
            $this->addError('cliente_id', 'El carrito esta vacio.');
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

        $breakdown = $this->calcularTotales($items);

        $sessionId = session()->getId();
        try {
            [$pedido, $pago] = DB::transaction(function () use ($items, $comprobantePath, $breakdown, $sessionId) {
                $this->stockReservaService->assertVisibleEcommerceCart($items, true);
                $this->stockReservaService->assertSessionActive($sessionId);

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

            $subtotalBase = 0;
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

                $subtotalBase += ((float) $item['precio']) * ((int) $item['cantidad']);
            }

            $pedido->update([
                'subtotal_pedido' => $breakdown['subtotal_base'],
                'iva_pedido' => $breakdown['iva_monto'],
                'descuentos_pedido' => $breakdown['descuento_monto'],
                'total_pedido' => $breakdown['total_final'],
            ]);

            $pago = Pago::create([
                'pedido_id' => $pedido->id,
                'metodo' => $this->metodo_pago,
                'estado' => $estadoPago,
                'monto' => $breakdown['total_final'],
                'moneda' => 'USD',
                'referencia_externa' => $this->referencia_transferencia,
                'comprobante_path' => $comprobantePath,
                'metadata' => [
                    'origen' => 'checkout_shop',
                    'subtotal_base' => $breakdown['subtotal_base'],
                    'iva_percent' => $breakdown['iva_percent'],
                    'iva_monto' => $breakdown['iva_monto'],
                    'payphone_fee_percent' => $breakdown['payphone_fee_percent'],
                    'payphone_fee_monto' => $breakdown['payphone_fee_monto'],
                    'descuento_monto' => $breakdown['descuento_monto'],
                    'total_final' => $breakdown['total_final'],
                ],
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

            $this->stockReservaService->consumeSessionReservations($sessionId);
            session()->forget('carrito');

                return [$pedido, $pago];
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Uno o más productos de tu carrito ya no están disponibles.') {
                session()->forget('carrito');
                $this->items = [];
                $this->stockReservaService->releaseSession($sessionId);
                $this->addError('cliente_id', 'Uno o más productos de tu carrito ya no están disponibles.');
                return null;
            }

            throw $e;
        }

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
                return redirect()->route('payments.payphone.box', $pago->id);
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
        return $this->getPricingBreakdownProperty()['total_final'];
    }

    public function getPricingBreakdownProperty(): array
    {
        return $this->calcularTotales($this->items);
    }

    private function calcularTotales(array $items): array
    {
        $subtotalBase = round(
            collect($items)->sum(fn ($item) => ((float) ($item['precio'] ?? 0)) * ((int) ($item['cantidad'] ?? 0))),
            2
        );

        $ivaPercent = max(0, (float) $this->ivaPercent);
        $payphoneFeePercent = max(0, (float) $this->payphoneFeePercent);

        $ivaMonto = round($subtotalBase * ($ivaPercent / 100), 2);
        $payphoneFeeMonto = $this->metodo_pago === 'payphone'
            ? round($subtotalBase * ($payphoneFeePercent / 100), 2)
            : 0.0;

        $descuentoMonto = 0.0;
        $totalFinal = round($subtotalBase + $ivaMonto + $payphoneFeeMonto - $descuentoMonto, 2);

        return [
            'subtotal_base' => $subtotalBase,
            'iva_percent' => $ivaPercent,
            'iva_monto' => $ivaMonto,
            'payphone_fee_percent' => $payphoneFeePercent,
            'payphone_fee_monto' => $payphoneFeeMonto,
            'descuento_monto' => $descuentoMonto,
            'total_final' => max(0, $totalFinal),
        ];
    }

    public function mount(): void
    {
        $fakeMode = (bool) config('payments.fake_mode', false);
        $this->paypalDisponible = $fakeMode || $this->paymentConfigService->isAvailable('paypal');
        $this->payphoneDisponible = $fakeMode || $this->paymentConfigService->isAvailable('payphone');
        $cfgPayphone = $this->paymentConfigService->get('payphone');
        $this->ivaPercent = $this->globalParameterService->getNumber(
            'iva_general',
            (float) data_get($cfgPayphone, 'settings.iva_percent', 0)
        );
        $this->payphoneFeePercent = $this->globalParameterService->getNumber(
            'recargo_payphone',
            (float) data_get($cfgPayphone, 'settings.payphone_fee_percent', 0)
        );

        if (!$this->paypalDisponible && $this->metodo_pago === 'paypal') {
            $this->metodo_pago = 'transferencia';
        }
        if (!$this->payphoneDisponible && $this->metodo_pago === 'payphone') {
            $this->metodo_pago = 'transferencia';
        }

        $this->items = session()->get('carrito', []);
        $this->reconcileCart();
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

    private function reconcileCart(bool $requireActiveReservation = false): bool
    {
        $storedItems = session()->get('carrito', []);
        $visibleItems = $this->stockReservaService->filterVisibleEcommerceItems($storedItems);
        $removedUnavailable = count($visibleItems) !== count($storedItems);
        $this->items = $visibleItems;

        try {
            if (empty($visibleItems)) {
                session()->forget('carrito');
                $this->stockReservaService->releaseSession(session()->getId());
            } else {
                session()->put('carrito', $visibleItems);
                if ($requireActiveReservation) {
                    $this->stockReservaService->assertSessionActive(session()->getId());
                }
                $this->stockReservaService->syncFromCart(session()->getId(), $visibleItems);
            }
        } catch (\Throwable $e) {
            session()->forget('carrito');
            $this->items = [];
            $this->stockReservaService->releaseSession(session()->getId());
            $this->addError('cliente_id', $e->getMessage());
            return false;
        }

        if ($removedUnavailable) {
            $this->addError('cliente_id', 'Uno o más productos de tu carrito ya no están disponibles.');
            return false;
        }

        return true;
    }

    public function render()
    {
        return view('livewire.ecommerce.checkout');
    }
}
