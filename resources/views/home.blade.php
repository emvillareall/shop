@extends('layouts.app')

@section('template_title')
    Caja | Venta fisica
@endsection

@section('content')
<style>
    .pos-shell { display: grid; gap: 16px; }
    .pos-top {
        display: grid;
        gap: 8px;
        grid-template-columns: minmax(180px, 1fr) minmax(220px, 1fr) minmax(220px, 1fr) minmax(220px, 1fr);
        align-items: start;
    }
    .pos-top .pos-kpi { grid-column: span 2; }
    .pos-top .card .card-header { padding: 10px 14px; }
    .pos-top .card .card-body { padding: 8px 12px; }
    .pos-top .card .card-body input,
    .pos-top .card .card-body select { height: 36px; }
    .pos-products { display: block; }
    .pos-bottom { display: grid; justify-content: end; }
    .pos-bottom-panel { width: min(520px, 100%); display: grid; gap: 12px; }
    .pos-product-zone-grid { display: grid; gap: 16px; grid-template-columns: minmax(0, 1fr) 360px; }
    .pos-product-preview { align-self: start; }
    .client-suggest-list { max-height: 220px; overflow: auto; }
    .client-search-wrap { position: relative; margin-bottom: 10px; }
    .client-suggest-list {
        margin-top: 8px;
        border-radius: 12px;
        border: 1px solid #dbe3ee;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
        background: #fff;
    }
    .client-suggest-item {
        display: block;
        width: 100%;
        border: 0;
        border-bottom: 1px solid #eef2f7;
        padding: 10px 12px;
        text-align: left;
        background: #fff;
        font-size: 14px;
    }
    .client-suggest-item:last-child { border-bottom: 0; }
    .client-suggest-item:hover { background: #f8fafc; }
    .client-selected-card {
        margin-top: 14px;
        border-radius: 12px;
        border: 1px solid #d6b7fb;
        background: #faf5ff;
        padding: 12px 14px;
    }
    .pos-kpi {
        border-radius: 14px;
        border: 1px solid #d9c2f5;
        background: linear-gradient(180deg, #ffffff 0%, #f9f4ff 100%);
        padding: 6px 8px;
        box-shadow: 0 8px 22px rgba(88, 28, 135, 0.08);
    }
    .pos-kpi-value {
        font-size: 1rem;
        line-height: 1.1;
        font-weight: 800;
        color: #5b21b6;
    }
    .pos-kpi-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; align-items: start; }
    .pos-kpi-stack { display: grid; gap: 6px; }
    .pos-kpi .rounded-xl { padding: 6px !important; border-radius: 10px; }
    .pos-kpi .btn { height: 30px; font-size: 12px; }
    .pos-kpi p { margin-bottom: 2px; }
    .pos-kpi input {
        height: 36px !important;
        padding: 4px 10px !important;
        font-size: 13px !important;
    }
    .pos-kpi label,
    .pos-kpi .text-xs {
        font-size: 11px !important;
        line-height: 1.15 !important;
    }
    .pos-kpi .kpi-row {
        min-height: 62px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .pos-kpi .kpi-row input { margin-top: 4px; }
    .pos-pill-btn {
        border: 1px solid #c4b5fd;
        background: #fff;
        color: #4c1d95;
        border-radius: 999px;
        padding: 8px 12px;
        font-weight: 600;
        font-size: 13px;
    }
    .pos-pill-btn.active {
        background: #6d28d9;
        color: #fff;
        border-color: #6d28d9;
        box-shadow: 0 8px 16px rgba(109, 40, 217, 0.25);
    }
    .pos-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        z-index: 80;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .pos-modal-card {
        width: min(560px, 100%);
        border-radius: 16px;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 24px 50px rgba(15, 23, 42, 0.28);
        overflow: hidden;
    }

    @media (max-width: 1200px) {
        .pos-top { grid-template-columns: 1fr; }
        .pos-product-zone-grid { grid-template-columns: 1fr; }
        .pos-bottom { justify-content: stretch; }
        .pos-bottom-panel { width: 100%; }
    }
</style>
@php
    $productosMap = collect($productos)->mapWithKeys(function ($p) {
        $imagen = !empty($p->imagen_producto)
            ? asset('media/productos/' . ltrim($p->imagen_producto, '/'))
            : 'https://via.placeholder.com/320x220/f1f5f9/94a3b8?text=Producto';
        return [(string) $p->id => [
            'id' => (string) $p->id,
            'nombre' => $p->descripcion_producto,
            'stock' => (int) $p->stock_venta_producto,
            'precio' => (float) ($p->precio_venta_producto ?? 0),
            'imagen' => $imagen,
        ]];
    });
    $posConfig = [
        'variantesPorProducto' => $variantesPorProducto ?? [],
        'productosMap' => $productosMap,
        'productoId' => (string) ($productos->first()->id ?? ''),
        'clientes' => collect($clientes)->map(function ($c) {
            return [
                'id' => (string) $c->id,
                'cedula' => (string) ($c->cedula_clientes ?? ''),
                'nombres' => trim((string) ($c->nombres_clientes ?? '')),
                'apellidos' => trim((string) ($c->apellidos_clientes ?? '')),
            ];
        })->values(),
        'selectedClienteId' => (string) old('clientes_id', ''),
        'clienteModo' => old('cliente_modo', 'existente'),
    ];
@endphp

<script>
    window.__posConfig = @json($posConfig);
</script>

<div x-data="posVenta(window.__posConfig || {})" class="space-y-4 pos-shell">
    @if ($message = Session::get('success'))
        <div class="alert alert-success">{{ $message }}</div>
    @elseif($message = Session::get('danger'))
        <div class="alert alert-danger">{{ $message }}</div>
    @endif
    @includeif('partials.errors')

    <form method="POST" action="{{ route('home.venta_fisica') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="lineas_json" :value="JSON.stringify(lineasPedido)">
        <input type="hidden" name="descuento_extra" :value="descuentoExtra">
        <input type="hidden" name="recargo_extra" :value="recargoExtra">
        <input type="hidden" name="metodo_pago_pos" :value="metodoPagoPos">
        <input type="hidden" name="modalidad_venta" :value="modalidadVenta">
        <input type="hidden" name="abono_inicial" :value="abonoInicial">
        <input type="hidden" name="referencia_pago_pos" :value="referenciaPagoPos">

        <div class="pos-top">
            <div class="card">
                <div class="card-header"><span class="text-base font-semibold">Datos de la tienda</span></div>
                <div class="card-body grid gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Tienda</label>
                        <select name="tienda_id" required>
                            <option value="">Seleccione tienda</option>
                            @foreach($tiendas as $tienda)
                                <option value="{{ $tienda->id }}" @selected((string)old('tienda_id') === (string)$tienda->id)>{{ $tienda->nombre_tienda }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Descripcion</label>
                        <input type="text" name="descripcion" value="{{ old('descripcion') }}" placeholder="Ej: Venta mostrador">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><span class="text-base font-semibold">Datos del cliente nuevo o ya registrado</span></div>
                <div class="card-body space-y-3">
                    <div class="flex flex-wrap gap-3">
                        <label class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            <input type="radio" name="cliente_modo" value="existente" x-model="clienteModo">
                            Cliente registrado
                        </label>
                        <label class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            <input type="radio" name="cliente_modo" value="nuevo" x-model="clienteModo">
                            Cliente nuevo
                        </label>
                    </div>

                    <div x-show="clienteModo === 'existente'" x-cloak>
                        <input type="hidden" name="clientes_id" :value="selectedClienteId">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Buscar cliente</label>
                        <div class="client-search-wrap">
                            <input type="text" x-model="clienteQuery" @focus="clienteOpen = true" @click.away="clienteOpen = false"
                                   placeholder="Buscar por cedula, nombre o apellido">
                            <div x-show="clienteOpen && clienteQuery.trim().length >= 2 && clientesFiltrados().length > 0"
                                 class="client-suggest-list absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg">
                                <template x-for="cliente in clientesFiltrados()" :key="cliente.id">
                                    <button type="button"
                                        class="client-suggest-item"
                                        @click="seleccionarCliente(cliente)">
                                        <span class="font-semibold" x-text="`${cliente.apellidos} ${cliente.nombres}`"></span>
                                        <span class="text-slate-500" x-text="` | ${cliente.cedula}`"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <template x-if="clienteSeleccionado()">
                            <div class="client-selected-card text-sm">
                                <p class="font-semibold text-slate-900" x-text="`${clienteSeleccionado().apellidos} ${clienteSeleccionado().nombres}`"></p>
                                <p class="text-slate-600">Cédula: <span class="font-semibold" x-text="clienteSeleccionado().cedula"></span></p>
                            </div>
                        </template>
                    </div>

                    <div x-show="clienteModo === 'nuevo'" x-cloak class="grid gap-2 md:grid-cols-2">
                        <input type="text" name="nombres_clientes" value="{{ old('nombres_clientes') }}" placeholder="Nombres">
                        <input type="text" name="apellidos_clientes" value="{{ old('apellidos_clientes') }}" placeholder="Apellidos">
                        <input type="text" name="cedula_clientes" value="{{ old('cedula_clientes') }}" placeholder="Cedula">
                        <input type="text" name="telefono_clientes" value="{{ old('telefono_clientes') }}" placeholder="Telefono">
                        <input type="text" name="ciudad_clientes" value="{{ old('ciudad_clientes') }}" placeholder="Ciudad">
                        <input type="text" name="direccion_clientes" value="{{ old('direccion_clientes') }}" placeholder="Direccion">
                        <input type="email" name="email_clientes" value="{{ old('email_clientes') }}" placeholder="Email" class="md:col-span-2">
                    </div>
                </div>
            </div>

            <div class="pos-kpi">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Resumen de caja</p>
                <div class="mt-2 grid gap-2">
                    <div class="pos-kpi-grid-2">
                        <div class="pos-kpi-stack">
                            <div class="rounded-xl border border-slate-200 bg-white p-2 kpi-row">
                                <p class="text-xs text-slate-500">Unidades seleccionadas</p>
                                <p class="pos-kpi-value" x-text="totalPedidoUnidades()"></p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-2 kpi-row">
                                <p class="text-xs text-slate-500">Subtotal productos</p>
                                <p class="pos-kpi-value">$<span x-text="money(totalLineasAgregadas())"></span></p>
                            </div>
                        </div>
                        <div class="pos-kpi-stack">
                            <div class="rounded-xl border border-slate-200 bg-white p-2 kpi-row">
                                <label class="text-xs text-slate-500">Recargo</label>
                                <input type="number" min="0" step="0.01" x-model.number="recargoExtra" class="mt-1">
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-2 kpi-row">
                                <label class="text-xs text-slate-500">Descuento</label>
                                <input type="number" min="0" step="0.01" x-model.number="descuentoExtra" class="mt-1">
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-purple-200 bg-purple-50 p-2">
                        <p class="text-xs text-slate-500">Total a pagar</p>
                        <p class="pos-kpi-value">$<span x-text="money(totalFacturaEstimado())"></span></p>
                    </div>
                    <button type="button" class="btn btn-primary w-full" @click="modalPagoOpen = true">
                        Configurar forma de pago
                    </button>
                    <button type="submit" class="btn btn-dark w-full"
                            :disabled="lineasPedido.length < 1 || (metodoPagoPos === 'transferencia' && !String(referenciaPagoPos || '').trim()) || (modalidadVenta === 'apartado' && Number(abonoInicial || 0) <= 0)">
                        Confirmar venta
                    </button>
                    <p class="text-xs text-slate-500">
                        Modalidad: <span class="font-semibold uppercase text-slate-800" x-text="modalidadVenta"></span> |
                        Pago elegido: <span class="font-semibold uppercase text-slate-800" x-text="metodoPagoPos"></span>
                        <template x-if="metodoPagoPos === 'transferencia' && referenciaPagoPos">
                            <span class="text-slate-700"> | Ref: <span x-text="referenciaPagoPos"></span></span>
                        </template>
                        <template x-if="modalidadVenta === 'apartado'">
                            <span class="text-slate-700"> | Abono inicial: $<span x-text="money(abonoInicial)"></span></span>
                        </template>
                    </p>
                </div>
            </div>
        </div>

        <div class="card pos-products">
            <div class="card-header"><span class="text-base font-semibold">Zona de seleccion de productos</span></div>
            <div class="card-body">
                <div class="pos-product-zone-grid">
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-slate-700">Producto</label>
                            <select x-model="productoId" @change="cargarVariantes()">
                                @foreach($productos as $producto)
                                    <option value="{{ $producto->id }}">{{ $producto->descripcion_producto }} (stock: {{ $producto->stock_venta_producto }})</option>
                                @endforeach
                            </select>
                        </div>

                        <template x-if="variantes.length === 0">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-500">Sin variantes disponibles.</div>
                        </template>

                        <div class="space-y-2">
                            <template x-for="(item, idx) in variantes" :key="idx">
                                <div class="grid items-center gap-2 rounded-md bg-slate-50 p-2 md:grid-cols-[24px_1fr_120px]">
                                    <span class="inline-block h-5 w-5 rounded-full border border-slate-300" :style="`background-color:${item.codigo_color}`"></span>
                                    <span class="text-sm text-slate-700">
                                        <span x-text="item.nombre_color"></span> - talla <span class="font-semibold" x-text="item.talla_por_color"></span> - stock <span x-text="item.stock_por_color"></span>
                                    </span>
                                    <input type="number" min="0" :max="item.stock_por_color" class="text-center" :value="getCantidad(item)" @input="setCantidad(item, $event.target.value)">
                                </div>
                            </template>
                        </div>

                        <div class="flex items-center justify-between rounded-md border border-slate-200 bg-white px-3 py-2 text-xs">
                            <span class="font-semibold text-slate-600">Total unidades seleccionadas</span>
                            <span class="rounded-full bg-brand-50 px-2.5 py-1 font-bold text-brand-700" x-text="totalSeleccionado"></span>
                        </div>

                        <button type="button" class="btn btn-dark" :disabled="totalSeleccionado < 1" @click="agregarSeleccionActual()">Agregar seleccion</button>
                    </div>

                    <aside class="space-y-3 rounded-xl border border-slate-200 bg-white p-3 pos-product-preview">
                        <div class="overflow-hidden rounded-lg border border-slate-200">
                            <img :src="productoActualImagen()" alt="Producto" class="h-28 w-full object-cover">
                            <p class="px-3 py-2 text-sm font-semibold text-slate-700" x-text="productoActualNombre()"></p>
                        </div>

                        <div class="max-h-56 space-y-2 overflow-auto pr-1">
                            <template x-if="lineasPedido.length === 0">
                                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-3 text-xs text-slate-500">No hay productos agregados.</div>
                            </template>
                            <template x-for="(linea, idx) in lineasPedido" :key="`${linea.producto_id}-${linea.colores_id}-${linea.talla_por_color}-${idx}`">
                                <div class="rounded-lg border border-slate-200 p-2">
                                    <div class="text-sm font-semibold text-slate-800" x-text="linea.producto_nombre"></div>
                                    <div class="text-xs text-slate-500"><span x-text="linea.nombre_color"></span> · talla <span x-text="linea.talla_por_color"></span></div>
                                    <div class="mt-1 flex items-center justify-between">
                                        <div class="text-xs text-slate-600">Cant: <span x-text="linea.cantidad"></span></div>
                                        <div class="flex gap-1">
                                            <button type="button" class="btn btn-sm" @click="actualizarLinea(idx, linea.cantidad - 1)">-</button>
                                            <button type="button" class="btn btn-sm" @click="actualizarLinea(idx, linea.cantidad + 1)">+</button>
                                            <button type="button" class="btn btn-sm btn-danger" @click="eliminarLinea(idx)">Quitar</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary w-full" :disabled="lineasPedido.length < 1 || (metodoPagoPos === 'transferencia' && !String(referenciaPagoPos || '').trim()) || (modalidadVenta === 'apartado' && Number(abonoInicial || 0) <= 0)">
                                Confirmar venta
                            </button>
                        </div>
                    </aside>
                </div>
            </div>
        </div>

        <div class="hidden"></div>
    </form>

    <div x-show="modalPagoOpen" x-cloak class="pos-modal-overlay" @keydown.escape.window="modalPagoOpen = false">
    <div class="pos-modal-card" @click.away="modalPagoOpen = false">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-lg font-bold text-slate-900">Formas de pago</h3>
            <p class="text-sm text-slate-500">Selecciona la forma de cobro para esta factura.</p>
        </div>
        <div class="space-y-4 px-5 py-4">
            <div class="grid grid-cols-2 gap-2">
                <button type="button" class="pos-pill-btn" :class="{ 'active': modalidadVenta === 'contado' }" @click="modalidadVenta = 'contado'">Contado</button>
                <button type="button" class="pos-pill-btn" :class="{ 'active': modalidadVenta === 'apartado' }" @click="modalidadVenta = 'apartado'">Apartado</button>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" class="pos-pill-btn" :class="{ 'active': metodoPagoPos === 'efectivo' }" @click="metodoPagoPos = 'efectivo'">Efectivo</button>
                <button type="button" class="pos-pill-btn" :class="{ 'active': metodoPagoPos === 'transferencia' }" @click="metodoPagoPos = 'transferencia'">Transferencia</button>
                <button type="button" class="pos-pill-btn" :class="{ 'active': metodoPagoPos === 'paypal' }" @click="metodoPagoPos = 'paypal'">PayPal</button>
                <button type="button" class="pos-pill-btn" :class="{ 'active': metodoPagoPos === 'payphone' }" @click="metodoPagoPos = 'payphone'">Payphone</button>
            </div>
            <template x-if="modalidadVenta === 'apartado'">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Abono inicial</label>
                    <input type="number" min="0.01" step="0.01" x-model.number="abonoInicial" placeholder="Ej: 20.00">
                    <p class="mt-1 text-xs text-slate-500">Saldo pendiente estimado: $<span x-text="money(Math.max(0, totalFacturaEstimado() - Number(abonoInicial || 0)))"></span></p>
                </div>
            </template>
            <template x-if="metodoPagoPos === 'transferencia'">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Número de transferencia</label>
                    <input type="text" x-model="referenciaPagoPos" placeholder="Ej: TRX-00991283">
                </div>
            </template>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
            <button type="button" class="btn" @click="modalPagoOpen = false">Cerrar</button>
            <button type="button" class="btn btn-primary" @click="modalPagoOpen = false">Guardar selección</button>
        </div>
    </div>
    </div>
</div>

<script>
    function posVenta(config) {
        return {
            productoId: String(config.productoId || ''),
            variantesPorProducto: config.variantesPorProducto || {},
            productosMap: config.productosMap || {},
            clientes: config.clientes || [],
            selectedClienteId: String(config.selectedClienteId || ''),
            clienteModo: String(config.clienteModo || 'existente'),
            clienteQuery: '',
            clienteOpen: false,
            variantes: [],
            cantidades: {},
            lineasPedido: [],
            totalSeleccionado: 0,
            descuentoExtra: 0,
            recargoExtra: 0,
            metodoPagoPos: '{{ old('metodo_pago_pos', 'efectivo') }}',
            modalidadVenta: '{{ old('modalidad_venta', 'contado') }}',
            abonoInicial: {{ (float) old('abono_inicial', 0) }},
            referenciaPagoPos: '{{ old('referencia_pago_pos', '') }}',
            modalPagoOpen: false,
            init() { this.cargarVariantes(); },
            keyVariante(item) { return `${item.colores_id}__${item.talla_por_color}`; },
            getCantidad(item) { return Number(this.cantidades[this.keyVariante(item)] || 0); },
            setCantidad(item, value) {
                const key = this.keyVariante(item);
                const stock = Number(item.stock_por_color || 0);
                let qty = Number(value || 0);
                if (!Number.isFinite(qty)) qty = 0;
                qty = Math.max(0, Math.min(stock, Math.trunc(qty)));
                this.cantidades[key] = qty;
                this.recalcularTotal();
            },
            recalcularTotal() { this.totalSeleccionado = Object.values(this.cantidades).reduce((a, n) => a + (Number(n) || 0), 0); },
            cargarVariantes() {
                const rows = this.variantesPorProducto?.[String(this.productoId)] ?? this.variantesPorProducto?.[Number(this.productoId)] ?? [];
                this.variantes = Array.isArray(rows) ? rows.filter((i) => Number(i.stock_por_color || 0) > 0) : [];
                this.cantidades = {};
                this.variantes.forEach((i) => this.cantidades[this.keyVariante(i)] = 0);
                this.recalcularTotal();
            },
            agregarSeleccionActual() {
                const seleccion = this.variantes.filter((item) => this.getCantidad(item) > 0).map((item) => ({
                    producto_id: Number(this.productoId),
                    producto_nombre: this.productosMap?.[String(this.productoId)]?.nombre || `Producto #${this.productoId}`,
                    colores_id: Number(item.colores_id),
                    nombre_color: item.nombre_color,
                    talla_por_color: item.talla_por_color,
                    stock_por_color: Number(item.stock_por_color || 0),
                    cantidad: this.getCantidad(item),
                }));
                for (const nuevo of seleccion) {
                    const ix = this.lineasPedido.findIndex((x) => Number(x.producto_id) === Number(nuevo.producto_id) && Number(x.colores_id) === Number(nuevo.colores_id) && String(x.talla_por_color) === String(nuevo.talla_por_color));
                    if (ix >= 0) this.lineasPedido[ix].cantidad = Math.min(this.lineasPedido[ix].cantidad + nuevo.cantidad, this.lineasPedido[ix].stock_por_color);
                    else this.lineasPedido.push(nuevo);
                }
                this.cargarVariantes();
            },
            actualizarLinea(index, nextValue) {
                if (!this.lineasPedido[index]) return;
                let qty = Number(nextValue || 0);
                if (!Number.isFinite(qty)) qty = 0;
                qty = Math.max(0, Math.min(Number(this.lineasPedido[index].stock_por_color || 0), Math.trunc(qty)));
                if (qty === 0) return this.lineasPedido.splice(index, 1);
                this.lineasPedido[index].cantidad = qty;
            },
            eliminarLinea(index) { this.lineasPedido.splice(index, 1); },
            totalPedidoUnidades() { return this.lineasPedido.reduce((acc, row) => acc + (Number(row.cantidad) || 0), 0); },
            totalLineasAgregadas() {
                return this.lineasPedido.reduce((acc, row) => {
                    const precio = Number(this.productosMap?.[String(row.producto_id)]?.precio || 0);
                    return acc + (precio * (Number(row.cantidad) || 0));
                }, 0);
            },
            totalFacturaEstimado() { return Math.max(0, (this.totalLineasAgregadas() + Number(this.recargoExtra || 0)) - Number(this.descuentoExtra || 0)); },
            productoActualNombre() { return this.productosMap?.[String(this.productoId)]?.nombre || 'Producto'; },
            productoActualImagen() { return this.productosMap?.[String(this.productoId)]?.imagen || 'https://via.placeholder.com/320x220/f1f5f9/94a3b8?text=Producto'; },
            clientesFiltrados() {
                const q = String(this.clienteQuery || '').trim().toLowerCase();
                if (q.length < 2) return [];
                return this.clientes.filter((c) => {
                    const full = `${c.apellidos} ${c.nombres}`.toLowerCase();
                    return full.includes(q) || String(c.cedula || '').toLowerCase().includes(q);
                }).slice(0, 12);
            },
            clienteSeleccionado() {
                return this.clientes.find((x) => String(x.id) === String(this.selectedClienteId)) || null;
            },
            seleccionarCliente(cliente) {
                this.selectedClienteId = String(cliente.id);
                this.clienteQuery = `${cliente.apellidos} ${cliente.nombres}`;
                this.clienteOpen = false;
            },
            money(value) { const n = Number(value || 0); return n.toFixed(2); },
        };
    }
</script>
@endsection
