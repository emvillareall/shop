@php
    $productosMap = collect($productos)->mapWithKeys(function ($p) {
        $imagen = !empty($p->imagen_producto)
            ? asset('storage/' . ltrim($p->imagen_producto, '/'))
            : 'https://via.placeholder.com/320x220/f1f5f9/94a3b8?text=Producto';
        return [(string) $p->id => [
            'id' => (string) $p->id,
            'nombre' => $p->descripcion_producto,
            'stock' => (int) $p->stock_venta_producto,
            'precio' => (float) ($p->precio_venta_producto ?? 0),
            'imagen' => $imagen,
        ]];
    });

    $detallePedidoFormConfig = [
        'productoId' => (string) old('producto_id', $productos[0]->id ?? ''),
        'endpointTemplate' => route('admin.productos.variantes', ['producto' => '__PRODUCTO__']),
        'variantesPorProducto' => $variantesPorProducto ?? [],
        'productosMap' => $productosMap,
        'subtotalBase' => (float) ($pedido->subtotal_pedido ?? 0),
        'descuentoBase' => (float) ($pedido->descuentos_pedido ?? 0),
    ];
@endphp

<script>
    window.__detallePedidoFormConfig = @json($detallePedidoFormConfig);
</script>

<style>
    .dp-layout {
        display: block;
    }

    @media (min-width: 1200px) {
        .dp-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 420px;
            gap: 16px;
            align-items: start;
        }

        .dp-side {
            position: sticky;
            top: 80px;
            max-height: calc(100vh - 96px);
            overflow: auto;
        }

        .dp-side-list {
            max-height: calc(100vh - 360px);
            overflow: auto;
        }
    }
</style>

<div x-data="detallePedidoForm(window.__detallePedidoFormConfig || {})"
     x-init="cargarVariantes()"
     class="dp-layout">
    <div class="min-w-0 space-y-4">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700" for="producto_id">Producto</label>
                <select id="producto_id" name="producto_id" x-model="productoId" @change="cargarVariantes()">
                    @foreach($productos as $producto)
                        <option value="{{ $producto->id }}">
                            {{ $producto->descripcion_producto }} (stock: {{ $producto->stock_venta_producto }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Pedido ID</label>
                <input type="text" value="{{ $pedido_id }}" readonly>
                <input type="hidden" name="pedido_id" value="{{ $pedido_id }}">
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700">Colores y tallas disponibles</h3>
                <span x-show="cargando" class="text-xs text-slate-500">Cargando...</span>
            </div>

            <template x-if="errorCarga">
                <p class="mb-2 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700" x-text="errorCarga"></p>
            </template>

            <template x-if="!cargando && variantes.length === 0">
                <p class="text-sm text-slate-500">No hay variantes disponibles para este producto.</p>
            </template>

            <div class="space-y-2">
                <template x-for="(item, idx) in variantes" :key="idx">
                    <div class="grid items-center gap-2 rounded-md bg-white p-2 md:grid-cols-[24px_1fr_120px]">
                        <span class="inline-block h-5 w-5 rounded-full border border-slate-300" :style="`background-color:${item.codigo_color}`"></span>
                        <span class="text-sm text-slate-700">
                            <span x-text="item.nombre_color"></span>
                            -
                            talla <span class="font-semibold" x-text="item.talla_por_color"></span>
                            -
                            stock <span class="font-semibold" x-text="item.stock_por_color"></span>
                        </span>
                        <input type="number"
                               min="0"
                               :max="item.stock_por_color"
                               class="text-center"
                               :disabled="Number(item.stock_por_color) < 1"
                               :value="getCantidad(item)"
                               @input="setCantidad(item, $event.target.value)"
                               @change="setCantidad(item, $event.target.value)"
                               :name="`cantidad_${item.colores_id}_${item.talla_por_color}`">
                    </div>
                </template>
            </div>

            <div class="mt-3 flex items-center justify-between rounded-md border border-slate-200 bg-white px-3 py-2 text-xs">
                <span class="font-semibold text-slate-600">Total unidades seleccionadas</span>
                <span class="rounded-full bg-brand-50 px-2.5 py-1 font-bold text-brand-700" x-text="totalSeleccionado"></span>
            </div>

            <div class="mt-3 flex justify-end">
                <button type="button"
                        class="btn btn-dark disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="totalSeleccionado < 1"
                        @click="agregarSeleccionActual()">
                    Agregar selección al pedido
                </button>
            </div>
        </div>

        <input type="hidden" name="lineas_json" :value="JSON.stringify(lineasPedido)">
        <input type="hidden" name="descuento_extra" :value="descuentoExtra">
        <input type="hidden" name="recargo_extra" :value="recargoExtra">
    </div>

    <aside class="dp-side w-full rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <img :src="productoActualImagen()" alt="Producto seleccionado" class="h-36 w-full object-cover">
            <p class="border-t border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" x-text="productoActualNombre()"></p>
        </div>

        <div class="my-4 h-3 rounded bg-slate-100"></div>

        <div class="mb-3 border-b border-slate-200 pb-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Menú de venta</p>
            <p class="mt-1 text-sm font-semibold text-slate-800">Productos agregados al pedido</p>
        </div>

        <div class="mb-4 grid grid-cols-2 gap-2 text-xs">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-2">
                <p class="text-slate-500">Líneas</p>
                <p class="text-lg font-bold text-slate-900" x-text="lineasPedido.length"></p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-2">
                <p class="text-slate-500">Unidades</p>
                <p class="text-lg font-bold text-brand-700" x-text="totalPedidoUnidades()"></p>
            </div>
        </div>

        <div class="mb-3 flex items-center justify-between">
            <p class="text-sm font-semibold text-slate-700">Líneas cargadas</p>
            <button type="button" class="text-xs font-semibold text-slate-500 hover:text-rose-600" @click="vaciarPedido()">
                Vaciar todo
            </button>
        </div>

        <div class="dp-side-list space-y-2 pr-1">
            <template x-if="lineasPedido.length === 0">
                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-3 text-xs text-slate-500">
                    No hay productos agregados todavía.
                </div>
            </template>

            <template x-for="(linea, idx) in lineasPedido" :key="`${linea.producto_id}-${linea.colores_id}-${linea.talla_por_color}-${idx}`">
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-3 w-3 rounded-full border border-slate-300" :style="`background-color:${linea.codigo_color}`"></span>
                        <p class="text-sm font-semibold text-slate-800 truncate" x-text="linea.producto_nombre"></p>
                    </div>
                    <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                        <span class="inline-block h-4 w-4 rounded-full border border-slate-300 shadow-sm" :style="`background-color:${linea.codigo_color}`" title="Color"></span>
                        <span class="rounded-md border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[11px] font-medium text-slate-600" x-text="linea.nombre_color"></span>
                        <span>· talla <span x-text="linea.talla_por_color"></span></span>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <p class="text-xs text-slate-500">Stock: <span class="font-semibold" x-text="linea.stock_por_color"></span></p>
                        <div class="flex items-center gap-1">
                            <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100" @click="actualizarLinea(idx, linea.cantidad - 1)">-</button>
                            <span class="inline-flex min-w-8 justify-center text-sm font-semibold text-slate-900" x-text="linea.cantidad"></span>
                            <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100" @click="actualizarLinea(idx, linea.cantidad + 1)">+</button>
                            <button type="button" class="ml-1 rounded-md border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50" @click="eliminarLinea(idx)">Quitar</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Resumen factura</p>
            <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                <div class="rounded-lg border border-slate-200 bg-white p-2">
                    <p class="text-slate-500">Subtotal base</p>
                    <p class="font-semibold text-slate-800">$<span x-text="money(subtotalBase)"></span></p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-2">
                    <p class="text-slate-500">Agregado actual</p>
                    <p class="font-semibold text-slate-800">$<span x-text="money(totalLineasAgregadas())"></span></p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-2">
                    <p class="text-slate-500">Recargo cierre</p>
                    <input type="number" min="0" step="0.01" x-model.number="recargoExtra" class="mt-1 text-sm">
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-2">
                    <p class="text-slate-500">Descuento cierre</p>
                    <input type="number" min="0" step="0.01" x-model.number="descuentoExtra" class="mt-1 text-sm">
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-2 col-span-2">
                    <p class="text-slate-500">Total estimado factura</p>
                    <p class="text-lg font-bold text-brand-700">$<span x-text="money(totalFacturaEstimado())"></span></p>
                </div>
            </div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Acciones de venta</p>
            <div class="flex flex-wrap items-center gap-2">
                <button type="submit"
                        name="accion"
                        value="guardar"
                        class="btn btn-primary disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="cargando || totalPedidoUnidades() < 1">
                    Guardar factura
                </button>
                <button type="submit"
                        name="accion"
                        value="emitir_imprimir"
                        class="btn btn-dark disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="cargando || totalPedidoUnidades() < 1">
                    Emitir e imprimir
                </button>
                <a href="{{ request('return_to') ?: route('pedidos.index') }}" class="btn">Volver</a>
            </div>
        </div>
    </aside>
</div>

<script>
    function detallePedidoForm(config) {
        return {
            productoId: String(config.productoId || ''),
            endpointTemplate: String(config.endpointTemplate || ''),
            variantesPorProducto: config.variantesPorProducto || {},
            productosMap: config.productosMap || {},
            variantes: [],
            cantidades: {},
            lineasPedido: [],
            subtotalBase: Number(config.subtotalBase || 0),
            descuentoBase: Number(config.descuentoBase || 0),
            descuentoExtra: 0,
            recargoExtra: 0,
            cargando: false,
            errorCarga: '',
            totalSeleccionado: 0,
            keyVariante(item) {
                return `${item.colores_id}__${item.talla_por_color}`;
            },
            getCantidad(item) {
                const key = this.keyVariante(item);
                return Number(this.cantidades[key] || 0);
            },
            setCantidad(item, value) {
                const key = this.keyVariante(item);
                const stock = Number(item.stock_por_color || 0);
                let qty = Number(value || 0);
                if (!Number.isFinite(qty)) qty = 0;
                qty = Math.max(0, Math.min(stock, Math.trunc(qty)));
                this.cantidades[key] = qty;
                this.recalcularTotal();
            },
            limpiarSeleccionActual() {
                for (const item of this.variantes) {
                    this.cantidades[this.keyVariante(item)] = 0;
                }
                this.recalcularTotal();
            },
            agregarSeleccionActual() {
                const seleccion = this.variantes
                    .filter((item) => this.getCantidad(item) > 0)
                    .map((item) => ({
                        producto_id: Number(this.productoId),
                        producto_nombre: this.productosMap?.[String(this.productoId)]?.nombre || `Producto #${this.productoId}`,
                        producto_imagen: this.productosMap?.[String(this.productoId)]?.imagen || 'https://via.placeholder.com/320x220/f1f5f9/94a3b8?text=Producto',
                        colores_id: Number(item.colores_id),
                        nombre_color: item.nombre_color,
                        codigo_color: item.codigo_color,
                        talla_por_color: item.talla_por_color,
                        stock_por_color: Number(item.stock_por_color || 0),
                        cantidad: this.getCantidad(item),
                    }));

                for (const nuevo of seleccion) {
                    const ix = this.lineasPedido.findIndex((x) =>
                        Number(x.producto_id) === Number(nuevo.producto_id) &&
                        Number(x.colores_id) === Number(nuevo.colores_id) &&
                        String(x.talla_por_color) === String(nuevo.talla_por_color)
                    );
                    if (ix >= 0) {
                        const merged = this.lineasPedido[ix].cantidad + nuevo.cantidad;
                        this.lineasPedido[ix].cantidad = Math.min(merged, this.lineasPedido[ix].stock_por_color);
                    } else {
                        this.lineasPedido.push(nuevo);
                    }
                }

                this.limpiarSeleccionActual();
            },
            actualizarLinea(index, nextValue) {
                if (!this.lineasPedido[index]) return;
                let qty = Number(nextValue || 0);
                if (!Number.isFinite(qty)) qty = 0;
                qty = Math.max(0, Math.min(Number(this.lineasPedido[index].stock_por_color || 0), Math.trunc(qty)));
                if (qty === 0) {
                    this.lineasPedido.splice(index, 1);
                    return;
                }
                this.lineasPedido[index].cantidad = qty;
            },
            eliminarLinea(index) {
                if (!this.lineasPedido[index]) return;
                this.lineasPedido.splice(index, 1);
            },
            vaciarPedido() {
                this.lineasPedido = [];
            },
            productoActualNombre() {
                return this.productosMap?.[String(this.productoId)]?.nombre || 'Producto';
            },
            productoActualImagen() {
                return this.productosMap?.[String(this.productoId)]?.imagen || 'https://via.placeholder.com/320x220/f1f5f9/94a3b8?text=Producto';
            },
            totalPedidoUnidades() {
                return this.lineasPedido.reduce((acc, row) => acc + (Number(row.cantidad) || 0), 0);
            },
            totalLineasAgregadas() {
                return this.lineasPedido.reduce((acc, row) => {
                    const precio = Number(this.productosMap?.[String(row.producto_id)]?.precio || 0);
                    return acc + (precio * (Number(row.cantidad) || 0));
                }, 0);
            },
            totalFacturaEstimado() {
                const bruto = this.subtotalBase + this.totalLineasAgregadas() + Number(this.recargoExtra || 0);
                return Math.max(0, bruto - (this.descuentoBase + Number(this.descuentoExtra || 0)));
            },
            money(value) {
                const n = Number(value || 0);
                return n.toFixed(2);
            },
            recalcularTotal() {
                this.totalSeleccionado = Object.values(this.cantidades)
                    .reduce((acc, n) => acc + (Number(n) || 0), 0);
            },
            inicializarCantidades() {
                const next = {};
                for (const item of this.variantes) {
                    next[this.keyVariante(item)] = 0;
                }
                this.cantidades = next;
                this.recalcularTotal();
            },
            async cargarVariantes() {
                if (!this.productoId) {
                    this.variantes = [];
                    this.cantidades = {};
                    this.errorCarga = '';
                    this.totalSeleccionado = 0;
                    return;
                }

                this.cargando = true;
                this.errorCarga = '';
                try {
                    const endpoint = this.endpointTemplate.replace('__PRODUCTO__', this.productoId);
                    const locales = this.variantesPorProducto?.[String(this.productoId)] ?? this.variantesPorProducto?.[Number(this.productoId)] ?? null;
                    if (Array.isArray(locales) && locales.length > 0) {
                        this.variantes = locales.filter((item) => Number(item.stock_por_color || 0) > 0);
                        this.inicializarCantidades();
                        return;
                    }

                    const response = await fetch(endpoint, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    });
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    const payload = await response.json();
                    this.variantes = Array.isArray(payload)
                        ? payload.filter((item) => Number(item.stock_por_color || 0) > 0)
                        : [];
                    this.inicializarCantidades();
                } catch (e) {
                    this.variantes = [];
                    this.cantidades = {};
                    this.errorCarga = `No se pudo cargar variantes (${e?.message || 'error inesperado'}).`;
                } finally {
                    this.cargando = false;
                }
            }
        };
    }
</script>
