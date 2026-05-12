@extends('layouts.app')

@section('template_title')
    Ventas
@endsection

@section('content')
    <div x-data="{ abonoOpen: false, abonoRuta: '', abonoVenta: '', saldoPendiente: '0.00' }" class="space-y-4">
        <div class="card">
            <div class="card-header">
                <span class="text-base font-semibold">Ventas registradas</span>
            </div>
            <div class="card-body">
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[260px] flex-1">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Buscar</label>
                        <input type="text" name="q" value="{{ $q }}" placeholder="Codigo, cliente o tienda">
                    </div>
                    <div class="min-w-[220px]">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Estado venta</label>
                        <select name="estado">
                            <option value="">Todos</option>
                            @foreach(['BORRADOR','PENDIENTE_PAGO','PAGO_EN_REVISION','PAGADO','EN_PREPARACION','DESPACHADO','ENTREGADO','CANCELADO','RECHAZADO','DEVUELTO'] as $opt)
                                <option value="{{ $opt }}" @selected($estado === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">Aplicar</button>
                        <a href="{{ route('ventas.index') }}" class="btn btn-sm">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Tienda</th>
                            <th>Subtotal</th>
                            <th>Descuento</th>
                            <th>Total</th>
                            <th>Abonado</th>
                            <th>Saldo</th>
                            <th>Estado venta</th>
                            <th>Acciones</th>
                            <th>Fecha</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($ventas as $venta)
                            @php
                                $total = (float)($venta->total ?? 0);
                                $abonado = (float)($venta->total_abonado ?? 0);
                                $saldo = max(0, $total - $abonado);
                                $esApartado = strtoupper((string)($venta->modalidad_venta ?? 'CONTADO')) === 'APARTADO';
                            @endphp
                            <tr>
                                <td>{{ $ventas->firstItem() + $loop->index }}</td>
                                <td>{{ $venta->codigo_pedido ?: ('#'.$venta->pedido_id) }}</td>
                                <td>{{ trim(($venta->nombres_clientes ?? '').' '.($venta->apellidos_clientes ?? '')) ?: '-' }}</td>
                                <td>{{ $venta->nombre_tienda ?: '-' }}</td>
                                <td>${{ number_format((float)$venta->subtotal, 2) }}</td>
                                <td>${{ number_format((float)$venta->descuento, 2) }}</td>
                                <td class="font-semibold">${{ number_format((float)$venta->total, 2) }}</td>
                                <td><span class="font-semibold text-emerald-700">${{ number_format($abonado, 2) }}</span></td>
                                <td>
                                    @if($saldo <= 0.00001)
                                        <span class="badge bg-success">Completado</span>
                                    @else
                                        <span class="badge bg-warning">${{ number_format($saldo, 2) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="badge bg-info">{{ $venta->estado_venta }}</span>
                                        @if($esApartado && $saldo > 0.00001)
                                            <span class="badge bg-purple-100 text-purple-800">Apartado</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($esApartado && $saldo > 0.00001)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary"
                                            @click="
                                                abonoOpen = true;
                                                abonoRuta = '{{ route('ventas.abonos.store', $venta->id) }}';
                                                abonoVenta = '{{ addslashes($venta->codigo_pedido ?: ('#'.$venta->pedido_id)) }}';
                                                saldoPendiente = '{{ number_format($saldo, 2, '.', '') }}';
                                            ">
                                            Registrar abono
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-500">Sin accion</span>
                                    @endif
                                </td>
                                <td>{{ optional($venta->fecha_venta)->format('Y-m-d H:i') ?: optional($venta->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="py-8 text-center text-slate-500">No hay ventas registradas.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $ventas->links() }}</div>
            </div>
        </div>

        <div x-show="abonoOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
            <div class="absolute inset-0 bg-slate-900/60" @click="abonoOpen = false"></div>
            <div class="relative z-10 w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
                <h3 class="text-lg font-semibold text-slate-900">Registrar abono</h3>
                <p class="mt-1 text-sm text-slate-600">Venta: <span class="font-semibold" x-text="abonoVenta"></span></p>
                <p class="mb-3 text-sm text-amber-700">Saldo pendiente: $<span x-text="saldoPendiente"></span></p>
                <form method="POST" :action="abonoRuta" class="space-y-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Monto</label>
                        <input type="number" name="monto" min="0.01" step="0.01" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Metodo</label>
                        <select name="metodo" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="paypal">PayPal</option>
                            <option value="payphone">Payphone</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Referencia (opcional)</label>
                        <input type="text" name="referencia">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Observacion (opcional)</label>
                        <textarea name="observacion" rows="2"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn btn-sm" @click="abonoOpen = false">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary">Guardar abono</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
