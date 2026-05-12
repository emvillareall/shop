@extends('layouts.app')

@section('template_title')
    Apartados
@endsection

@section('content')
    <div class="space-y-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="text-base font-semibold">Control de apartados</span>
                <a href="{{ route('ventas.index') }}" class="btn btn-sm btn-outline-secondary">Ir a ventas</a>
            </div>
            <div class="card-body space-y-3">
                <div class="grid gap-3 md:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Pendientes</p>
                        <p class="text-xl font-bold text-slate-900">{{ (int)($apartadosMetrics['pendientes'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2">
                        <p class="text-xs uppercase tracking-wide text-amber-700">Vencidos hoy</p>
                        <p class="text-xl font-bold text-amber-800">{{ (int)($apartadosMetrics['vencidos_hoy'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2">
                        <p class="text-xs uppercase tracking-wide text-indigo-700">Por cobrar semana</p>
                        <p class="text-xl font-bold text-indigo-800">{{ (int)($apartadosMetrics['semana'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2">
                        <p class="text-xs uppercase tracking-wide text-emerald-700">Saldo total</p>
                        <p class="text-xl font-bold text-emerald-800">${{ number_format((float)($apartadosMetrics['saldo_total'] ?? 0), 2) }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="font-semibold text-slate-600">Leyenda:</span>
                    <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 font-semibold text-rose-700">Vencido hoy</span>
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 font-semibold text-amber-700">Vence esta semana</span>
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 font-semibold text-emerald-700">Al día</span>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">Sin fecha</span>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Abonado</th>
                            <th>Saldo</th>
                            <th>Próximo abono</th>
                            <th>Atraso</th>
                            <th>Prioridad</th>
                            <th>Cobro rápido</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($apartados as $ap)
                            @php
                                $saldo = (float)($ap->saldo ?? 0);
                                $hoy = \Carbon\Carbon::now()->startOfDay();
                                $finSemana = \Carbon\Carbon::now()->endOfWeek();
                                $prioridadLabel = 'Sin fecha';
                                $prioridadClass = 'bg-slate-100 text-slate-700';
                                if (!empty($ap->fecha_proximo_abono)) {
                                    $proximo = \Carbon\Carbon::parse($ap->fecha_proximo_abono);
                                    if ($proximo->lessThanOrEqualTo($hoy)) {
                                        $prioridadLabel = 'Vencido hoy';
                                        $prioridadClass = 'bg-rose-100 text-rose-700';
                                    } elseif ($proximo->between($hoy, $finSemana, true)) {
                                        $prioridadLabel = 'Vence esta semana';
                                        $prioridadClass = 'bg-amber-100 text-amber-700';
                                    } else {
                                        $prioridadLabel = 'Al día';
                                        $prioridadClass = 'bg-emerald-100 text-emerald-700';
                                    }
                                }
                            @endphp
                            <tr>
                                <td>{{ $ap->codigo_pedido ?: ('#'.$ap->pedido_id) }}</td>
                                <td>{{ trim(($ap->nombres_clientes ?? '').' '.($ap->apellidos_clientes ?? '')) }}</td>
                                <td>${{ number_format((float)($ap->total ?? 0), 2) }}</td>
                                <td>${{ number_format((float)($ap->total_abonado ?? 0), 2) }}</td>
                                <td class="font-semibold text-amber-700">${{ number_format($saldo, 2) }}</td>
                                <td>
                                    @if(!empty($ap->fecha_proximo_abono))
                                        {{ \Carbon\Carbon::parse($ap->fecha_proximo_abono)->format('Y-m-d') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($ap->fecha_proximo_abono) && (int)($ap->dias_atraso ?? 0) > 0)
                                        <span class="text-rose-700 font-semibold">{{ (int)$ap->dias_atraso }} día(s)</span>
                                    @else
                                        <span class="text-slate-500">0</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $prioridadClass }}">
                                        {{ $prioridadLabel }}
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('ventas.abonos.store', $ap->id) }}" enctype="multipart/form-data" class="d-flex align-items-center gap-2 flex-wrap">
                                        @csrf
                                        <input
                                            type="number"
                                            name="monto"
                                            min="0.01"
                                            step="0.01"
                                            max="{{ max(0, (float)($ap->saldo ?? 0)) }}"
                                            value="{{ number_format(max(0, (float)($ap->saldo ?? 0)), 2, '.', '') }}"
                                            class="form-control form-control-sm"
                                            style="max-width: 110px;"
                                            required
                                        >
                                        <select name="metodo" class="form-control form-control-sm" style="max-width: 140px;" required>
                                            <option value="efectivo">Efectivo</option>
                                            <option value="transferencia">Transferencia</option>
                                            <option value="paypal">PayPal</option>
                                            <option value="payphone">Payphone</option>
                                        </select>
                                        <input
                                            type="text"
                                            name="referencia"
                                            class="form-control form-control-sm"
                                            style="max-width: 140px;"
                                            placeholder="Referencia"
                                            title="Obligatorio en transferencia si no subes comprobante"
                                        >
                                        <input
                                            type="file"
                                            name="comprobante"
                                            class="form-control form-control-sm"
                                            style="max-width: 170px;"
                                            accept=".jpg,.jpeg,.png,.pdf,.webp"
                                            title="Comprobante (imagen o PDF)"
                                        >
                                        <button type="submit" class="btn btn-sm btn-primary">Cobrar</button>
                                        <button type="submit"
                                                class="btn btn-sm btn-success"
                                                onclick="this.form.monto.value='{{ number_format(max(0, (float)($ap->saldo ?? 0)), 2, '.', '') }}'">
                                            Cobrar todo
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-3">No hay apartados pendientes por ahora.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {!! $apartados->links() !!}
    </div>
@endsection
