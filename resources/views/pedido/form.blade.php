@php
    $selectedCliente = old('clientes_id', $pedido->clientes_id ?? '');
    $selectedTienda = old('tienda_id', $pedido->tienda_id ?? '');
    $tipoCliente = old('tipo_cliente', 'existente');
@endphp

<div x-data="{ tipoCliente: '{{ $tipoCliente }}', q: '' }" class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
        <label class="mb-2 block text-sm font-semibold text-slate-700">Tipo de cliente</label>
        <div class="flex flex-wrap gap-3">
            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                <input type="radio" name="tipo_cliente" value="existente" x-model="tipoCliente">
                Cliente registrado
            </label>
            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                <input type="radio" name="tipo_cliente" value="nuevo" x-model="tipoCliente">
                Cliente nuevo (enviar formulario)
            </label>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-semibold text-slate-700" for="descripcion">Descripcion</label>
            <input id="descripcion" name="descripcion" type="text" value="{{ old('descripcion', $pedido->descripcion) }}" placeholder="Descripcion del pedido"
                   class="@error('descripcion') border-rose-400 @enderror">
            @error('descripcion')
            <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700" for="descuentos_pedido">Descuento</label>
            <input id="descuentos_pedido" name="descuentos_pedido" type="number" step="0.01" min="0" value="{{ old('descuentos_pedido', $pedido->descuentos_pedido ?? 0) }}"
                   class="@error('descuentos_pedido') border-rose-400 @enderror">
            @error('descuentos_pedido')
            <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
            @enderror
        </div>


        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700" for="estado_pago">Estado pago</label>
            <select id="estado_pago" name="estado_pago">
                @foreach(['SIN_PAGO','PENDIENTE','EN_REVISION','APROBADO','RECHAZADO','REEMBOLSADO'] as $estadoPago)
                    <option value="{{ $estadoPago }}" {{ old('estado_pago', $pedido->estado_pago ?? 'SIN_PAGO') === $estadoPago ? 'selected' : '' }}>{{ $estadoPago }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700" for="estado_envio">Estado envio</label>
            <select id="estado_envio" name="estado_envio">
                @foreach(['SIN_ENVIO','PENDIENTE','PREPARANDO','ENVIADO','ENTREGADO','NO_ENTREGADO'] as $estadoEnvio)
                    <option value="{{ $estadoEnvio }}" {{ old('estado_envio', $pedido->estado_envio ?? 'SIN_ENVIO') === $estadoEnvio ? 'selected' : '' }}>{{ $estadoEnvio }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700" for="estado_pedido">Estado pedido</label>
            <select id="estado_pedido" name="estado_pedido">
                @foreach(['BORRADOR','PENDIENTE_PAGO','PAGO_EN_REVISION','PAGADO','EN_PREPARACION','DESPACHADO','ENTREGADO','CANCELADO','RECHAZADO','DEVUELTO'] as $estado)
                    <option value="{{ $estado }}" {{ old('estado_pedido', $pedido->estado_pedido ?? 'PENDIENTE_PAGO') === $estado ? 'selected' : '' }}>{{ $estado }}</option>
                @endforeach
            </select>
        </div>

        <div x-show="tipoCliente === 'existente'" x-cloak>
            <label class="mb-1 block text-sm font-semibold text-slate-700" for="clientes_id">Cliente</label>
            <input x-model="q" type="text" placeholder="Buscar cliente..." class="mb-2">
            <select id="clientes_id" name="clientes_id" size="8" class="h-44 @error('clientes_id') border-rose-400 @enderror">
                @foreach($clientes as $cliente)
                    <option value="{{ $cliente->id }}"
                            {{ (string)$selectedCliente === (string)$cliente->id ? 'selected' : '' }}
                            x-show="'{{ strtolower($cliente->apellidos_clientes.' '.$cliente->nombres_clientes) }}'.includes(q.toLowerCase())">
                        {{ $cliente->apellidos_clientes }} {{ $cliente->nombres_clientes }}
                    </option>
                @endforeach
            </select>
            @error('clientes_id')
            <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
            @enderror
        </div>

        <div x-show="tipoCliente === 'nuevo'" x-cloak>
            <label class="mb-1 block text-sm font-semibold text-slate-700" for="telefono_nuevo">Telefono WhatsApp cliente</label>
            <input id="telefono_nuevo" name="telefono_nuevo" type="text" value="{{ old('telefono_nuevo') }}" placeholder="Ej: 0999999999"
                   class="@error('telefono_nuevo') border-rose-400 @enderror">
            <p class="mt-1 text-xs text-slate-500">Al guardar, se genera link firmado para copiar o enviar por WhatsApp.</p>
            <div class="mt-3 flex flex-wrap gap-3">
                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <input type="radio" name="modo_envio_link" value="whatsapp" @checked(old('modo_envio_link', 'whatsapp') === 'whatsapp')>
                    Enviar por WhatsApp
                </label>
                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <input type="radio" name="modo_envio_link" value="copiar" @checked(old('modo_envio_link') === 'copiar')>
                    Generar link para copiar
                </label>
            </div>
            @error('telefono_nuevo')
            <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700" for="tienda_id">Tienda</label>
            <select id="tienda_id" name="tienda_id" class="@error('tienda_id') border-rose-400 @enderror">
                <option value="">Seleccione tienda</option>
                @foreach($tienda_id as $id => $label)
                    <option value="{{ $id }}" {{ (string)$selectedTienda === (string)$id ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('tienda_id')
            <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="flex items-center gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar Pedido</button>
        <a href="{{ route('pedidos.index') }}" class="btn">Cancelar</a>
    </div>
</div>
