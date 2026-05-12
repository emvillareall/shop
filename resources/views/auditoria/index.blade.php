@extends('layouts.app')

@section('template_title')
    Auditoria
@endsection

@section('content')
    <style>
        .audit-shell { display: grid; gap: 16px; }
        .audit-kpi-grid { display: grid; gap: 12px; grid-template-columns: repeat(12, minmax(0, 1fr)); }
        .audit-kpi { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; padding: 12px 14px; box-shadow: 0 8px 24px rgba(15, 23, 42, .05); }
        .audit-kpi h6 { margin: 0; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
        .audit-kpi .v { margin-top: 4px; font-size: 26px; line-height: 1; font-weight: 800; color: #0f172a; }
        .audit-kpi-dominios { grid-column: span 6; }
        .audit-kpi-small { grid-column: span 3; }
        .audit-chip { display: inline-flex; align-items: center; border-radius: 999px; border: 1px solid #dbe3ee; background: #f8fafc; color: #334155; padding: 4px 10px; font-size: 12px; margin-right: 6px; margin-bottom: 6px; }
        .audit-filter-grid { display: grid; gap: 10px; grid-template-columns: repeat(12, minmax(0, 1fr)); }
        .audit-filter-grid .span-2 { grid-column: span 2; }
        .audit-filter-grid .span-1 { grid-column: span 1; }
        .audit-filter-grid .span-12 { grid-column: span 12; }
        .audit-compact th { font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: .03em; border-top: 0; }
        .audit-compact td { vertical-align: middle; }
        .audit-json {
            white-space: pre-wrap;
            font-size: 12px;
            line-height: 1.35;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
            max-height: 140px;
            overflow: auto;
            margin: 0;
        }
        .audit-table-wrap { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; }
        .audit-subcards { display: grid; gap: 12px; grid-template-columns: repeat(12, minmax(0, 1fr)); }
        .audit-subcards .span-6 { grid-column: span 6; }
        @media (max-width: 1200px) {
            .audit-kpi-dominios, .audit-kpi-small { grid-column: span 12; }
            .audit-filter-grid .span-2, .audit-filter-grid .span-1 { grid-column: span 6; }
        }
        @media (max-width: 768px) {
            .audit-filter-grid .span-2, .audit-filter-grid .span-1 { grid-column: span 12; }
            .audit-subcards .span-6 { grid-column: span 12; }
        }
    </style>

    <div class="container-fluid audit-shell">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="font-weight-bold">Auditoria del sistema</span>
                <small class="text-muted">Monitoreo operativo y trazabilidad</small>
            </div>
            <div class="card-body">
                <div class="audit-kpi-grid mb-3">
                    <div class="audit-kpi audit-kpi-small">
                        <h6>Total eventos</h6>
                        <div class="v">{{ $totalEventos }}</div>
                    </div>
                    <div class="audit-kpi audit-kpi-small">
                        <h6>Ultimas 24h</h6>
                        <div class="v">{{ $ultimas24h }}</div>
                    </div>
                    <div class="audit-kpi audit-kpi-dominios">
                        <h6>Distribucion por dominio</h6>
                        <div class="mt-2">
                            @forelse($porDominio as $d)
                                <span class="audit-chip">{{ strtoupper($d->dominio) }}: {{ $d->total }}</span>
                            @empty
                                <span class="text-muted">Sin datos</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ route('auditoria.index') }}" class="mb-3">
                    <div class="audit-filter-grid">
                        <div class="span-2">
                            <select name="dominio" class="form-control">
                                <option value="">Dominio (todos)</option>
                                <option value="inventario" {{ request('dominio') === 'inventario' ? 'selected' : '' }}>Inventario</option>
                                <option value="pedidos" {{ request('dominio') === 'pedidos' ? 'selected' : '' }}>Pedidos</option>
                                <option value="pagos" {{ request('dominio') === 'pagos' ? 'selected' : '' }}>Pagos</option>
                                <option value="compras" {{ request('dominio') === 'compras' ? 'selected' : '' }}>Compras</option>
                            </select>
                        </div>
                        <div class="span-2">
                            <input type="text" name="accion" value="{{ request('accion') }}" class="form-control" placeholder="Accion">
                        </div>
                        <div class="span-2">
                            <input type="text" name="entidad" value="{{ request('entidad') }}" class="form-control" placeholder="Entidad">
                        </div>
                        <div class="span-2">
                            <input type="number" name="user_id" value="{{ request('user_id') }}" class="form-control" placeholder="User ID">
                        </div>
                        <div class="span-2">
                            <input type="date" name="desde" value="{{ request('desde') }}" class="form-control">
                        </div>
                        <div class="span-2">
                            <input type="date" name="hasta" value="{{ request('hasta') }}" class="form-control">
                        </div>
                        <div class="span-1">
                            <button class="btn btn-primary btn-block" type="submit">Filtrar</button>
                        </div>
                        <div class="span-1">
                            <a class="btn btn-success btn-block" href="{{ route('auditoria.export_csv', request()->query()) }}">CSV</a>
                        </div>
                        <div class="span-1">
                            <a class="btn btn-outline-secondary btn-block" href="{{ route('auditoria.index') }}">Limpiar</a>
                        </div>
                    </div>
                </form>

                <div class="audit-subcards mb-3">
                    <div class="audit-table-wrap span-6">
                        <div class="px-3 py-2 border-bottom font-weight-semibold">Top acciones</div>
                        <div class="table-responsive">
                            <table class="table table-sm audit-compact mb-0">
                                <thead>
                                    <tr><th>Accion</th><th class="text-right">Total</th></tr>
                                </thead>
                                <tbody>
                                    @forelse($topAcciones as $a)
                                        <tr>
                                            <td>{{ $a->accion }}</td>
                                            <td class="text-right font-weight-semibold">{{ $a->total }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="text-muted">Sin datos</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="audit-table-wrap span-6">
                        <div class="px-3 py-2 border-bottom font-weight-semibold">Top usuarios</div>
                        <div class="table-responsive">
                            <table class="table table-sm audit-compact mb-0">
                                <thead>
                                    <tr><th>User ID</th><th class="text-right">Total</th></tr>
                                </thead>
                                <tbody>
                                    @forelse($topUsuarios as $u)
                                        <tr>
                                            <td>{{ $u->uid == 0 ? 'N/A' : $u->uid }}</td>
                                            <td class="text-right font-weight-semibold">{{ $u->total }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="text-muted">Sin datos</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="audit-table-wrap mb-3">
                    <div class="px-3 py-2 border-bottom font-weight-semibold">Tendencia (ultimos 14 dias)</div>
                    <div class="table-responsive">
                        <table class="table table-sm audit-compact mb-0">
                            <thead>
                                <tr><th>Fecha</th><th class="text-right">Eventos</th></tr>
                            </thead>
                            <tbody>
                                @forelse($tendencia as $t)
                                    <tr>
                                        <td>{{ $t->fecha }}</td>
                                        <td class="text-right font-weight-semibold">{{ $t->total }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-muted">Sin datos</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="audit-table-wrap">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th>User</th>
                                    <th>Accion</th>
                                    <th>Entidad</th>
                                    <th>ID Entidad</th>
                                    <th>IP</th>
                                    <th>Antes</th>
                                    <th>Despues</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td>{{ $log->id }}</td>
                                        <td>{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</td>
                                        <td>{{ $log->user_id ?? 'N/A' }}</td>
                                        <td><span class="badge badge-light">{{ $log->accion }}</span></td>
                                        <td>{{ $log->entidad }}</td>
                                        <td>{{ $log->entidad_id }}</td>
                                        <td>{{ $log->ip }}</td>
                                        <td><pre class="audit-json">{{ json_encode($log->valores_antes, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre></td>
                                        <td><pre class="audit-json">{{ json_encode($log->valores_despues, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-4">No hay eventos para los filtros seleccionados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        {!! $logs->links() !!}
    </div>
@endsection
