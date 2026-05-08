@extends('layouts.app')

@section('template_title')
    Ventas
@endsection

@section('content')
    <div class="space-y-4">
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
                            <th>Estado venta</th>
                            <th>Fecha</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($ventas as $venta)
                            <tr>
                                <td>{{ $ventas->firstItem() + $loop->index }}</td>
                                <td>{{ $venta->codigo_pedido ?: ('#'.$venta->pedido_id) }}</td>
                                <td>{{ trim(($venta->nombres_clientes ?? '').' '.($venta->apellidos_clientes ?? '')) ?: '-' }}</td>
                                <td>{{ $venta->nombre_tienda ?: '-' }}</td>
                                <td>${{ number_format((float)$venta->subtotal, 2) }}</td>
                                <td>${{ number_format((float)$venta->descuento, 2) }}</td>
                                <td class="font-semibold">${{ number_format((float)$venta->total, 2) }}</td>
                                <td><span class="badge bg-info">{{ $venta->estado_venta }}</span></td>
                                <td>{{ optional($venta->fecha_venta)->format('Y-m-d H:i') ?: optional($venta->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="py-8 text-center text-slate-500">No hay ventas registradas.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $ventas->links() }}</div>
            </div>
        </div>
    </div>
@endsection

