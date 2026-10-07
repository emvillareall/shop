@extends('layouts.app')

@section('template_title')
    Reclamo #{{ $reclamo->id }}
@endsection

@section('content')
    @php
        $claimStateClass = match ($reclamo->estado) {
            'RESUELTO' => 'bg-emerald-100 text-emerald-800',
            'CANCELADO' => 'bg-rose-100 text-rose-800',
            'EN_GESTION' => 'bg-amber-100 text-amber-800',
            default => 'bg-slate-100 text-slate-700',
        };
        $resolutionLabels = [
            'REPOSICION' => 'Reposición',
            'NOTA_CREDITO' => 'Nota de crédito',
            'REEMBOLSO' => 'Reembolso',
            'ACEPTADO_SIN_COMPENSACION' => 'Aceptado sin compensación',
            'OTRO' => 'Otro',
        ];
    @endphp

    @if($message = Session::get('success'))
        <div class="alert alert-success">{{ $message }}</div>
    @endif
    @if($message = Session::get('danger'))
        <div class="alert alert-danger">{{ $message }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="mb-5 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="mb-2 flex flex-wrap items-center gap-3">
                <span class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Gestión con proveedor</span>
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $claimStateClass }}">{{ $estadoLabels[$reclamo->estado] ?? $reclamo->estado }}</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Reclamo #{{ $reclamo->id }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                Compra #{{ $compra->id }} · {{ $compra->codigo_compra }} · {{ $compra->proveedor?->tienda_proveedor ?? 'Proveedor no especificado' }}
            </p>
            <dl class="mt-4 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                <div><dt class="font-semibold text-slate-800">Creado por</dt><dd>{{ $reclamo->creadoPor?->name ?? '—' }}</dd></div>
                <div><dt class="font-semibold text-slate-800">Fecha</dt><dd>{{ optional($reclamo->created_at)->format('d/m/Y H:i') ?? '—' }}</dd></div>
                <div><dt class="font-semibold text-slate-800">Referencia proveedor</dt><dd>{{ $reclamo->referencia_proveedor ?: '—' }}</dd></div>
                <div><dt class="font-semibold text-slate-800">Observación</dt><dd>{{ $reclamo->observacion ?: '—' }}</dd></div>
            </dl>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('compras.show', $compra) }}" class="btn btn-secondary">Volver a compra</a>
            @if($reclamo->estado === 'BORRADOR')
                <form method="POST" action="{{ route('compras.reclamos.enviar', [$compra, $reclamo]) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Enviar reclamo</button>
                </form>
            @elseif($reclamo->estado === 'ENVIADO')
                <form method="POST" action="{{ route('compras.reclamos.gestion', [$compra, $reclamo]) }}">
                    @csrf
                    <button class="btn btn-warning" type="submit">Marcar en gestión</button>
                </form>
            @endif
            @if(in_array($reclamo->estado, ['BORRADOR', 'ENVIADO', 'EN_GESTION'], true))
                <form method="POST" action="{{ route('compras.reclamos.cancelar', [$compra, $reclamo]) }}" onsubmit="return confirm('¿Cancelar este reclamo? Esta acción queda registrada.');">
                    @csrf
                    <input type="hidden" name="motivo" value="Cancelación administrativa desde el detalle.">
                    <button class="btn btn-outline-danger" type="submit">Cancelar</button>
                </form>
            @endif
        </div>
    </div>

    @if($reclamo->estado === 'BORRADOR' && $incidenciasReclamables->isNotEmpty())
        <section class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm md:p-5">
            <h2 class="text-lg font-bold text-slate-900">Agregar otra incidencia al borrador</h2>
            <p class="mt-1 text-sm text-slate-600">Solo se muestran saldos todavía reclamables de la compra.</p>
            <div class="mt-3 space-y-3">
                @foreach($incidenciasReclamables as $incidencia)
                    <form method="POST" action="{{ route('compras.reclamos.lineas.store', [$compra, $reclamo]) }}" class="rounded-lg border border-slate-200 bg-white p-3">
                        @csrf
                        <input type="hidden" name="compra_recepcion_item_id" value="{{ $incidencia['compra_recepcion_item_id'] }}">
                        <div class="grid gap-2 md:grid-cols-12 md:items-end">
                            <div class="md:col-span-4"><p class="font-semibold">{{ $incidencia['producto'] }}</p><p class="text-sm text-slate-500">{{ $incidencia['color'] }} · Talla {{ $incidencia['talla'] }}</p></div>
                            <label class="text-sm md:col-span-3">Tipo<select name="tipo_incidencia" class="form-control" required>@foreach($incidencia['tipos_disponibles'] as $tipo)<option value="{{ $tipo['tipo_incidencia'] }}">{{ $tipo['tipo_incidencia'] }} · saldo {{ $tipo['cantidad_reclamable'] }}</option>@endforeach</select></label>
                            <label class="text-sm md:col-span-2">Cantidad<input type="number" name="cantidad_reclamada" min="1" class="form-control" required></label>
                            <label class="text-sm md:col-span-3">Observación<input type="text" name="observacion" maxlength="1000" class="form-control"></label>
                        </div>
                        <button class="btn btn-sm btn-warning mt-3" type="submit">Agregar línea</button>
                    </form>
                @endforeach
            </div>
        </section>
    @endif

    @foreach($lineas as $linea)
        @php
            $modeloLinea = $linea['modelo'];
            $lineStateClass = match ($modeloLinea->estado) {
                'RESUELTO' => 'bg-emerald-100 text-emerald-800',
                'CANCELADO' => 'bg-rose-100 text-rose-800',
                'EN_GESTION' => 'bg-amber-100 text-amber-800',
                default => 'bg-slate-100 text-slate-700',
            };
        @endphp
        <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
            <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Línea #{{ $modeloLinea->id }} · {{ $modeloLinea->tipo_incidencia }}</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $linea['producto'] }}</h2>
                    <p class="text-sm text-slate-500">{{ $linea['color'] }} · Talla {{ $linea['talla'] }}</p>
                </div>
                <span class="self-start rounded-full px-3 py-1 text-xs font-bold {{ $lineStateClass }}">{{ $modeloLinea->estado }}</span>
            </div>

            <div class="mt-4 grid gap-3 text-sm sm:grid-cols-4">
                <div><span class="text-slate-500">Reclamada</span><p class="font-bold">{{ $linea['cantidad_reclamada'] }}</p></div>
                <div><span class="text-slate-500">Asignada a resolución</span><p class="font-bold">{{ $linea['cantidad_asignada'] }}</p></div>
                <div><span class="text-slate-500">Resuelta efectivamente</span><p class="font-bold text-emerald-700">{{ $linea['cantidad_finalizada'] }}</p></div>
                <div><span class="text-slate-500">Pendiente</span><p class="font-bold text-amber-700">{{ $linea['cantidad_pendiente_finalizar'] }}</p></div>
            </div>

            @if($linea['resoluciones']->isNotEmpty())
                <div class="mt-4 space-y-3">
                    @foreach($linea['resoluciones'] as $resumenResolucion)
                        @php
                            $resolucion = $resumenResolucion['modelo'];
                            $resolutionStateClass = $resolucion->estado === 'RESUELTA'
                                ? 'bg-emerald-100 text-emerald-800'
                                : ($resolucion->estado === 'CANCELADA' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800');
                        @endphp
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="font-bold text-slate-900">{{ $resolutionLabels[$resolucion->tipo_resolucion] ?? $resolucion->tipo_resolucion }}</p>
                                    <p class="text-sm text-slate-600">{{ $resolucion->cantidad }} unidad(es) · {{ $resolucion->referencia ?: 'Sin referencia' }}</p>
                                    @if($resolucion->importe !== null)
                                        <p class="text-sm text-slate-600">Importe documental: {{ $resolucion->importe }} {{ $resolucion->moneda ?: '' }}</p>
                                    @endif
                                    @if($resolucion->observacion)<p class="mt-1 text-sm text-slate-600">{{ $resolucion->observacion }}</p>@endif
                                </div>
                                <span class="self-start rounded-full px-3 py-1 text-xs font-bold {{ $resolutionStateClass }}">{{ $resolucion->estado }}</span>
                            </div>

                            @if($resolucion->tipo_resolucion === 'REPOSICION')
                                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm">
                                    <p class="font-bold text-amber-900">Reposición {{ $resolucion->estado === 'RESUELTA' ? 'cumplida' : 'pendiente' }}</p>
                                    <p class="text-amber-800">Prometidas: {{ $resolucion->cantidad }} · Aceptadas: {{ $resumenResolucion['cantidad_aceptada'] }} · Pendientes: {{ $resumenResolucion['cantidad_pendiente_fisica'] }}</p>
                                </div>
                                @if($resumenResolucion['recepciones']->isNotEmpty())
                                    <div class="mt-3 overflow-x-auto">
                                        <table class="table table-sm mb-0">
                                            <thead><tr><th>Fecha</th><th>Aceptadas</th><th>Dañadas</th><th>Incorrectas</th><th>Usuario</th><th>Observación</th></tr></thead>
                                            <tbody>
                                                @foreach($resumenResolucion['recepciones'] as $recepcionReposicion)
                                                    <tr>
                                                        <td>{{ optional($recepcionReposicion->fecha_recepcion)->format('d/m/Y H:i') }}</td>
                                                        <td>{{ $recepcionReposicion->cantidad_aceptada }}</td>
                                                        <td>{{ $recepcionReposicion->cantidad_danada }}</td>
                                                        <td>{{ $recepcionReposicion->cantidad_incorrecta }}</td>
                                                        <td>{{ $recepcionReposicion->recibidoPor?->name ?? '—' }}</td>
                                                        <td>{{ $recepcionReposicion->observacion ?: '—' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                                @if(!in_array($resolucion->estado, ['RESUELTA', 'CANCELADA'], true))
                                    <form method="POST" action="{{ route('compras.reclamos.reposiciones.store', [$compra, $reclamo, $resolucion]) }}" class="mt-4 rounded-lg border border-slate-200 bg-white p-3">
                                        @csrf
                                        <input type="hidden" name="idempotency_key" value="{{ $reposicionKeys[$resolucion->id] ?? '' }}">
                                        <p class="mb-2 font-semibold text-slate-800">Recibir reposición física</p>
                                        <div class="grid gap-2 sm:grid-cols-3">
                                            <label class="text-sm">Aceptadas<input type="number" name="cantidad_aceptada" min="0" value="0" class="form-control" required></label>
                                            <label class="text-sm">Dañadas<input type="number" name="cantidad_danada" min="0" value="0" class="form-control" required></label>
                                            <label class="text-sm">Incorrectas<input type="number" name="cantidad_incorrecta" min="0" value="0" class="form-control" required></label>
                                        </div>
                                        <label class="mt-2 block text-sm">Observación<input type="text" name="observacion" maxlength="2000" class="form-control"></label>
                                        <button class="btn btn-primary mt-3" type="submit">Registrar llegada</button>
                                    </form>
                                @endif
                            @elseif(in_array($resolucion->estado, ['PENDIENTE', 'EN_GESTION'], true) && $reclamo->estado !== 'CANCELADO')
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('compras.reclamos.resoluciones.resolver', [$compra, $reclamo, $resolucion]) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-success" type="submit">Marcar resuelta</button>
                                    </form>
                                </div>
                            @endif

                            @if(in_array($resolucion->estado, ['PENDIENTE', 'EN_GESTION'], true))
                                <form method="POST" action="{{ route('compras.reclamos.resoluciones.cancelar', [$compra, $reclamo, $resolucion]) }}" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end" onsubmit="return confirm('¿Cancelar esta resolución?');">
                                    @csrf
                                    <label class="text-sm">Motivo<input type="text" name="motivo" required maxlength="2000" class="form-control" value="Cancelación administrativa."></label>
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Cancelar resolución</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if($linea['cantidad_pendiente_asignar'] > 0 && in_array($reclamo->estado, ['ENVIADO', 'EN_GESTION'], true) && $modeloLinea->estado !== 'CANCELADO')
                <form method="POST" action="{{ route('compras.reclamos.resoluciones.store', [$compra, $reclamo, $modeloLinea]) }}" class="mt-4 rounded-xl border border-brand-200 bg-brand-50 p-4">
                    @csrf
                    <p class="font-bold text-slate-900">Registrar resolución</p>
                    <p class="text-sm text-slate-600">Saldo sin asignar: {{ $linea['cantidad_pendiente_asignar'] }}. Una reposición es solo una promesa hasta su recepción aceptada.</p>
                    <div class="mt-3 grid gap-2 md:grid-cols-6">
                        <label class="text-sm md:col-span-2">Tipo<select name="tipo_resolucion" class="form-control" required>
                            @foreach($resolutionLabels as $tipoResolucion => $label)<option value="{{ $tipoResolucion }}">{{ $label }}</option>@endforeach
                        </select></label>
                        <label class="text-sm">Cantidad<input type="number" name="cantidad" min="1" max="{{ $linea['cantidad_pendiente_asignar'] }}" class="form-control" required></label>
                        <label class="text-sm">Referencia<input type="text" name="referencia" maxlength="255" class="form-control"></label>
                        <label class="text-sm">Importe<input type="text" name="importe" inputmode="decimal" class="form-control"></label>
                        <label class="text-sm">Moneda<input type="text" name="moneda" maxlength="3" placeholder="USD" class="form-control"></label>
                    </div>
                    <label class="mt-2 block text-sm">Observación (obligatoria para OTRO)<textarea name="observacion" maxlength="2000" class="form-control" rows="2"></textarea></label>
                    <button class="btn btn-primary mt-3" type="submit">Registrar resolución</button>
                </form>
            @endif

            @if(in_array($modeloLinea->estado, ['PENDIENTE', 'EN_GESTION'], true) && $reclamo->estado !== 'CANCELADO')
                <form method="POST" action="{{ route('compras.reclamos.lineas.cancelar', [$compra, $reclamo, $modeloLinea]) }}" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end" onsubmit="return confirm('¿Cancelar esta línea?');">
                    @csrf
                    <label class="text-sm">Motivo<input type="text" name="motivo" required maxlength="2000" class="form-control" value="Cancelación administrativa."></label>
                    <button class="btn btn-sm btn-outline-danger" type="submit">Cancelar línea</button>
                </form>
            @endif
        </section>
    @endforeach

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
        <h2 class="text-lg font-bold text-slate-900">Historial del reclamo</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="table table-sm mb-0">
                <thead><tr><th>Fecha</th><th>Acción</th><th>Usuario</th><th>Estado</th><th>Observación</th></tr></thead>
                <tbody>
                    @forelse($eventos as $evento)
                        <tr>
                            <td>{{ optional($evento->fecha_evento)->format('d/m/Y H:i') }}</td>
                            <td>{{ $evento->accion }}</td>
                            <td>{{ $evento->usuario?->name ?? '—' }}</td>
                            <td>{{ $evento->estado_anterior ?: '—' }} @if($evento->estado_nuevo)→ {{ $evento->estado_nuevo }}@endif</td>
                            <td>{{ $evento->observacion ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">Sin eventos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
