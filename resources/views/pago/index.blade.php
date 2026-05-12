@extends('layouts.app')

@section('template_title')
    Pagos
@endsection

@section('content')
    <div class="space-y-4">
        @if ($message = Session::get('success'))
            <div class="alert alert-success">{{ $message }}</div>
        @elseif($message = Session::get('error'))
            <div class="alert alert-danger">{{ $message }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-base font-semibold">Revision de pagos</span>
                    <a href="{{ route('pagos.configuracion.index') }}" class="btn btn-sm btn-outline-primary">
                        Configurar pasarelas
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Metodo</th>
                                <th>Estado</th>
                                <th>Monto</th>
                                <th>Comprobante</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pagos as $pago)
                                <tr>
                                    <td>{{ $pagos->firstItem() + $loop->index }}</td>
                                    <td>{{ $pago->codigo_pedido ?? ('PED-' . $pago->pedido_id) }}</td>
                                    <td>{{ $pago->nombres_clientes }} {{ $pago->apellidos_clientes }}</td>
                                    <td>{{ strtoupper($pago->metodo) }}</td>
                                    <td>
                                        @php
                                            $estadoPagoLabel = \App\Models\Pedido::ESTADO_PAGO_LABELS[$pago->estado] ?? str_replace('_', ' ', $pago->estado);
                                            $providerConfirmed = (bool) data_get((array) ($pago->metadata ?? []), 'provider_confirmed', false);
                                        @endphp
                                        <span class="badge {{ $pago->estado === 'APROBADO' ? 'bg-success' : ($pago->estado === 'RECHAZADO' ? 'bg-danger' : 'bg-warning') }}">
                                            {{ $estadoPagoLabel }}
                                        </span>
                                        @if(in_array($pago->metodo, ['paypal', 'payphone'], true))
                                            <div class="mt-1 text-[11px] {{ $providerConfirmed ? 'text-emerald-700' : 'text-amber-700' }}">
                                                {{ $providerConfirmed ? 'Confirmado por proveedor' : 'Pendiente webhook proveedor' }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>${{ number_format((float) $pago->monto, 2) }}</td>
                                    <td>
                                        @if($pago->comprobante_path)
                                            <a class="btn btn-sm btn-secondary" target="_blank" href="{{ asset('storage/' . $pago->comprobante_path) }}">Ver</a>
                                        @else
                                            <span class="text-slate-500 text-xs">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap gap-1">
                                            @if(!in_array($pago->estado, ['APROBADO', 'RECHAZADO'], true))
                                                @if(in_array($pago->metodo, ['paypal', 'payphone'], true) && !$providerConfirmed)
                                                    <button type="button"
                                                        class="btn btn-sm btn-secondary"
                                                        title="Pago bloqueado hasta confirmacion final del proveedor (webhook)">
                                                        <i class="fa-solid fa-clock mr-1"></i>Esperando webhook
                                                    </button>
                                                @else
                                                    <form method="POST" action="{{ route('pagos.aprobar', $pago->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success">Aprobar</button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('pagos.rechazar', $pago->id) }}">
                                                    @csrf
                                                    <input type="hidden" name="observacion" value="Rechazado desde panel administrativo">
                                                    <button type="submit" class="btn btn-sm btn-danger">Rechazar</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-slate-500">No hay pagos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $pagos->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

