@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="card">
        <div class="card-header">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-base font-semibold">Pedido #{{ $pedido->id }}</h2>
                <a href="{{ request('return_to', route('pedidos.index')) }}" class="btn btn-secondary btn-sm">
                    Volver
                </a>
            </div>
        </div>
        <div class="card-body grid gap-2 sm:grid-cols-3">
            <div>
                <p class="text-xs text-slate-500">Estado</p>
                @if($pedido->estado_pedidos == 1)
                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Activo</span>
                @else
                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">Inactivo</span>
                @endif
            </div>
            <div>
                <p class="text-xs text-slate-500">Fecha</p>
                <p class="font-medium text-slate-800">{{ \Carbon\Carbon::parse($pedido->created_at)->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Total</p>
                <p class="font-semibold text-slate-900">${{ number_format($pedido->total_pedido, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="text-sm font-semibold">Detalle del pedido</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Color</th>
                        <th>Talla</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Subtotal</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($detalles as $d)
                        <tr>
                            <td>{{ $d->nombre_producto_snapshot ?? $d->descripcion_producto }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="inline-block h-4 w-4 rounded-full border border-slate-300" style="background-color: {{ $d->codigo_color }};"></span>
                                    <span>{{ $d->nombre_color }}</span>
                                </div>
                            </td>
                            <td>{{ $d->talla_mostrada }}</td>
                            <td>{{ $d->cantidad_producto }}</td>
                            <td>${{ number_format($d->precio_unitario_snapshot ?? $d->precio_venta_producto, 2) }}</td>
                            <td>${{ number_format($d->subtotal_linea ?? (($d->precio_venta_producto ?? 0) * $d->cantidad_producto), 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-slate-500">Sin detalles para este pedido.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
