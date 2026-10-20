<div class="grid gap-6 lg:grid-cols-3" wire:poll.1s="tickReserva">
    <section class="space-y-4 lg:col-span-2">
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-semibold">No se pudo continuar con el pago.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-purple-100 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold">Datos del cliente</h3>
            <p class="mb-3 mt-1 text-xs leading-5 text-slate-600">Verifica bien estos datos: se usarán para preparar la guía de envío y coordinar la entrega mediante Servientrega.</p>
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-900">
                La dirección debe incluir calle principal y secundaria, sector y una referencia que ayude a ubicarte.
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label for="checkout-cedula" class="mb-1 block text-xs font-semibold text-slate-700">Cédula / identificación</label>
                    <input id="checkout-cedula" type="text" wire:model.defer="cedula" maxlength="40" autocomplete="off" placeholder="Ej.: 1712345675 o pasaporte">
                    <p class="mt-1 text-xs text-slate-500">Aceptamos cédula ecuatoriana, RUC o pasaporte.</p>
                    @error('cedula') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="checkout-telefono" class="mb-1 block text-xs font-semibold text-slate-700">Teléfono de contacto</label>
                    <input id="checkout-telefono" type="tel" wire:model.defer="cliente.telefono_clientes" maxlength="40" autocomplete="tel" inputmode="tel" placeholder="Ej.: 098 721 4365">
                    <p class="mt-1 text-xs text-slate-500">Usaremos un celular ecuatoriano para coordinar la entrega.</p>
                    @error('cliente.telefono_clientes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="checkout-nombres" class="mb-1 block text-xs font-semibold text-slate-700">Nombres</label>
                    <input id="checkout-nombres" type="text" wire:model.defer="cliente.nombres_clientes" maxlength="120" autocomplete="given-name" placeholder="Ej.: Ana María">
                    @error('cliente.nombres_clientes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="checkout-apellidos" class="mb-1 block text-xs font-semibold text-slate-700">Apellidos</label>
                    <input id="checkout-apellidos" type="text" wire:model.defer="cliente.apellidos_clientes" maxlength="120" autocomplete="family-name" placeholder="Ej.: Pérez López">
                    @error('cliente.apellidos_clientes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="checkout-ciudad" class="mb-1 block text-xs font-semibold text-slate-700">Ciudad de destino</label>
                    <input id="checkout-ciudad" type="text" wire:model.defer="cliente.ciudad_clientes" maxlength="80" autocomplete="address-level2" placeholder="Ej.: Quito">
                    <p class="mt-1 text-xs text-slate-500">Indica la ciudad o cantón donde recibirás el pedido.</p>
                    @error('cliente.ciudad_clientes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="checkout-direccion" class="mb-1 block text-xs font-semibold text-slate-700">Dirección de entrega</label>
                    <input id="checkout-direccion" type="text" wire:model.defer="cliente.direccion_clientes" maxlength="255" autocomplete="street-address" placeholder="Av. principal y calle secundaria, sector y referencia">
                    <p class="mt-1 text-xs text-slate-500">Incluye números de casa o lote y un punto de referencia cuando aplique.</p>
                    @error('cliente.direccion_clientes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label for="checkout-email" class="mb-1 block text-xs font-semibold text-slate-700">Correo electrónico</label>
                    <input id="checkout-email" type="email" wire:model.defer="cliente.email_clientes" maxlength="120" autocomplete="email" placeholder="nombre@dominio.com">
                    @error('cliente.email_clientes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-500">El teléfono debe estar correcto para que el transportista pueda coordinar la entrega.</p>
        </div>

        <div class="space-y-2 rounded-2xl border border-purple-100 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold">Metodo de pago</h3>
            <div class="grid gap-3 sm:grid-cols-3">
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-sm transition
                    {{ $metodo_pago === 'transferencia' ? 'border-purple-300 bg-purple-50 text-purple-900' : 'border-slate-200 bg-white text-slate-700 hover:border-purple-200' }}">
                    <input type="radio" name="metodo_pago" wire:model.live="metodo_pago" value="transferencia">
                    Transferencia
                </label>
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-sm transition
                    {{ $metodo_pago === 'paypal' ? 'border-purple-300 bg-purple-50 text-purple-900' : 'border-slate-200 bg-white text-slate-700 hover:border-purple-200' }}">
                    <input type="radio" name="metodo_pago" wire:model.live="metodo_pago" value="paypal" @disabled(!$paypalDisponible)>
                    PayPal
                    @if(!$paypalDisponible)
                        <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500">No disponible</span>
                    @endif
                </label>
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-sm transition
                    {{ $metodo_pago === 'payphone' ? 'border-purple-300 bg-purple-50 text-purple-900' : 'border-slate-200 bg-white text-slate-700 hover:border-purple-200' }}">
                    <input type="radio" name="metodo_pago" wire:model.live="metodo_pago" value="payphone" @disabled(!$payphoneDisponible)>
                    Payphone
                    @if(!$payphoneDisponible)
                        <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500">No disponible</span>
                    @endif
                </label>
            </div>
            @error('metodo_pago') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

            @if($metodo_pago === 'transferencia')
                <div class="grid gap-3 pt-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Referencia</label>
                        <input type="text" wire:model.defer="referencia_transferencia" placeholder="Numero de referencia">
                        @error('referencia_transferencia') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Comprobante</label>
                        <input type="file" wire:model="comprobante_transferencia" accept=".jpg,.jpeg,.png,.pdf">
                        @error('comprobante_transferencia') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="comprobante_transferencia" class="mt-1 text-xs text-slate-500">Subiendo comprobante...</div>
                    </div>
                </div>
            @elseif($metodo_pago === 'paypal')
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm text-indigo-800">
                    PayPal seleccionado. Al confirmar, te redirigimos para completar el pago seguro.
                </div>
            @elseif($metodo_pago === 'payphone')
                <div class="rounded-xl border border-cyan-200 bg-cyan-50 px-3 py-2 text-sm text-cyan-800">
                    Payphone seleccionado. Al confirmar, abriremos la cajita de pago segura dentro del flujo de tu compra.
                </div>
            @endif
        </div>
    </section>

    <aside class="sticky top-20 space-y-4 self-start rounded-2xl border border-purple-100 bg-white p-5 shadow-sm">
        <h3 class="text-base font-semibold">Resumen</h3>
        <div class="space-y-2 text-sm">
            @forelse($items as $item)
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-medium">{{ $item['descripcion'] }}</p>
                        <p class="text-slate-500">{{ $item['color'] }} / {{ $item['talla'] }} x {{ $item['cantidad'] }}</p>
                    </div>
                    <p class="font-semibold">${{ number_format($item['precio'] * $item['cantidad'], 2) }}</p>
                </div>
            @empty
                <p class="text-slate-500">No hay items en carrito.</p>
            @endforelse
        </div>
        <div class="border-t border-purple-100 pt-3">
            <div class="space-y-1 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Subtotal</span>
                    <span class="font-semibold">${{ number_format($this->pricingBreakdown['subtotal_base'], 2) }}</span>
                </div>
                @if($this->pricingBreakdown['iva_percent'] > 0 && $this->pricingBreakdown['iva_monto'] > 0)
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">IVA ({{ number_format($this->pricingBreakdown['iva_percent'], 2) }}%)</span>
                        <span class="font-semibold">${{ number_format($this->pricingBreakdown['iva_monto'], 2) }}</span>
                    </div>
                @endif
                @if($metodo_pago === 'payphone')
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Recargo PayPhone ({{ number_format($this->pricingBreakdown['payphone_fee_percent'], 2) }}%)</span>
                        <span class="font-semibold">${{ number_format($this->pricingBreakdown['payphone_fee_monto'], 2) }}</span>
                    </div>
                @endif
            </div>
            <p class="mt-2 text-lg font-bold">Total: ${{ number_format($this->total, 2) }}</p>
            <p class="mt-2 inline-flex rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-700">
                Reserva activa:
                {{ str_pad((string) floor($secondsRemaining / 60), 2, '0', STR_PAD_LEFT) }}:{{ str_pad((string) ($secondsRemaining % 60), 2, '0', STR_PAD_LEFT) }}
            </p>
        </div>
        @error('cliente_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        <button wire:click="confirmarPedido"
                wire:loading.attr="disabled"
                wire:target="confirmarPedido,comprobante_transferencia"
                class="btn btn-primary btn-sm w-full rounded-xl disabled:cursor-not-allowed disabled:opacity-60">
            <span wire:loading.remove wire:target="confirmarPedido">
                @if($metodo_pago === 'paypal')
                    Continuar con PayPal
                @elseif($metodo_pago === 'payphone')
                    Ir a pagar con Payphone
                @else
                    Confirmar pedido
                @endif
            </span>
            <span wire:loading wire:target="confirmarPedido">Procesando...</span>
        </button>
        <p class="text-xs text-slate-500">Tu stock se reserva y descuenta por variante en el momento de confirmar.</p>
    </aside>
</div>
