@extends('layouts.app')

@section('template_title')
    Ver pedido
@endsection

@section('content')
    <div class="max-w-4xl space-y-4">
        <div class="card">
            <div class="card-header">
                <div class="flex items-center justify-between">
                    <span class="text-base font-semibold">Detalle del pedido</span>
                    <a class="btn btn-sm" href="{{ route('pedidos.index') }}">Volver</a>
                </div>
            </div>
            <div class="card-body space-y-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div><span class="text-xs text-slate-500">Codigo</span><div class="font-semibold">{{ $pedido->codigo_pedido ?? '-' }}</div></div>
                    <div><span class="text-xs text-slate-500">Descripcion</span><div class="font-semibold">{{ $pedido->descripcion ?? '-' }}</div></div>
                    <div><span class="text-xs text-slate-500">Cliente ID</span><div class="font-semibold">{{ $pedido->clientes_id }}</div></div>
                    <div><span class="text-xs text-slate-500">Tienda ID</span><div class="font-semibold">{{ $pedido->tienda_id }}</div></div>
                    <div><span class="text-xs text-slate-500">Estado pedido</span><div class="font-semibold">{{ $pedido->estado_pedido ?? '-' }}</div></div>
                    <div><span class="text-xs text-slate-500">Estado pago</span><div class="font-semibold">{{ $pedido->estado_pago ?? '-' }}</div></div>
                    <div><span class="text-xs text-slate-500">Estado envio</span><div class="font-semibold">{{ $pedido->estado_envio ?? '-' }}</div></div>
                    <div><span class="text-xs text-slate-500">Total</span><div class="font-semibold">${{ number_format((float)($pedido->total_pedido ?? 0), 2) }}</div></div>
                    <div><span class="text-xs text-slate-500">Total abonado</span><div class="font-semibold text-emerald-700">${{ number_format((float)($totalAbonado ?? 0), 2) }}</div></div>
                    <div><span class="text-xs text-slate-500">Saldo pendiente</span><div class="font-semibold text-amber-700">${{ number_format((float)($saldoPendiente ?? 0), 2) }}</div></div>
                </div>

                @if(($abonos ?? collect())->count() > 0)
                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <div class="mb-2 text-sm font-semibold text-slate-700">Historial de abonos</div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                    <th>Metodo</th>
                                    <th>Referencia</th>
                                    <th>Observacion</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($abonos as $abono)
                                    <tr>
                                        <td>{{ optional($abono->fecha_abono)->format('Y-m-d H:i') }}</td>
                                        <td>${{ number_format((float)$abono->monto, 2) }}</td>
                                        <td class="uppercase">{{ $abono->metodo }}</td>
                                        <td>{{ $abono->referencia ?: '-' }}</td>
                                        <td>{{ $abono->observacion ?: '-' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-2">
                    <div class="text-sm font-semibold text-slate-700">Formulario de cliente (reenvio)</div>
                    <div class="flex items-center gap-2">
                        <input id="pedido-link-show" type="text" readonly value="{{ $signedUrl }}" class="w-full">
                        <button type="button" class="btn btn-sm btn-dark" onclick="navigator.clipboard.writeText(document.getElementById('pedido-link-show').value)">Copiar link</button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($whatsappUrl)
                            <a href="{{ $whatsappUrl }}" target="_blank" class="btn btn-sm btn-success">Enviar por WhatsApp</a>
                        @endif
                        <form method="POST" action="{{ route('event.getLinkSubscribe', $pedido->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">Regenerar link</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
