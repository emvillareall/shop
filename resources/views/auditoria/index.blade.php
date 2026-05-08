@extends('layouts.app')

@section('template_title')
    Auditoria
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <span id="card_title">Auditoria del sistema</span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="alert alert-primary mb-2">
                                    <strong>Total eventos</strong><br>
                                    <span style="font-size: 1.25rem;">{{ $totalEventos }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="alert alert-info mb-2">
                                    <strong>Ultimas 24h</strong><br>
                                    <span style="font-size: 1.25rem;">{{ $ultimas24h }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-light mb-2">
                                    <strong>Distribucion por dominio:</strong>
                                    @foreach($porDominio as $d)
                                        <span class="badge bg-secondary ms-1">{{ $d->dominio }}: {{ $d->total }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('auditoria.index') }}" class="mb-3">
                            <div class="row g-2">
                                <div class="col-md-2">
                                    <select name="dominio" class="form-control">
                                        <option value="">Dominio (todos)</option>
                                        <option value="inventario" {{ request('dominio') === 'inventario' ? 'selected' : '' }}>Inventario</option>
                                        <option value="pedidos" {{ request('dominio') === 'pedidos' ? 'selected' : '' }}>Pedidos</option>
                                        <option value="pagos" {{ request('dominio') === 'pagos' ? 'selected' : '' }}>Pagos</option>
                                        <option value="compras" {{ request('dominio') === 'compras' ? 'selected' : '' }}>Compras</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="accion" value="{{ request('accion') }}" class="form-control" placeholder="Accion">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="entidad" value="{{ request('entidad') }}" class="form-control" placeholder="Entidad">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" name="user_id" value="{{ request('user_id') }}" class="form-control" placeholder="User ID">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" name="desde" value="{{ request('desde') }}" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" name="hasta" value="{{ request('hasta') }}" class="form-control">
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button class="btn btn-primary" type="submit">Filtrar</button>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <a class="btn btn-success" href="{{ route('auditoria.export_csv', request()->query()) }}">Exportar CSV</a>
                                </div>
                            </div>
                        </form>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">Top acciones</div>
                                    <div class="card-body p-2">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr><th>Accion</th><th class="text-end">Total</th></tr>
                                            </thead>
                                            <tbody>
                                                @forelse($topAcciones as $a)
                                                    <tr>
                                                        <td>{{ $a->accion }}</td>
                                                        <td class="text-end">{{ $a->total }}</td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="2" class="text-muted">Sin datos</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">Top usuarios</div>
                                    <div class="card-body p-2">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr><th>User ID</th><th class="text-end">Total</th></tr>
                                            </thead>
                                            <tbody>
                                                @forelse($topUsuarios as $u)
                                                    <tr>
                                                        <td>{{ $u->uid == 0 ? 'N/A' : $u->uid }}</td>
                                                        <td class="text-end">{{ $u->total }}</td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="2" class="text-muted">Sin datos</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">Tendencia (ultimos 14 dias)</div>
                            <div class="card-body p-2">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr><th>Fecha</th><th class="text-end">Eventos</th></tr>
                                    </thead>
                                    <tbody>
                                        @forelse($tendencia as $t)
                                            <tr>
                                                <td>{{ $t->fecha }}</td>
                                                <td class="text-end">{{ $t->total }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="2" class="text-muted">Sin datos</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead">
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
                                    @foreach ($logs as $log)
                                        <tr>
                                            <td>{{ $log->id }}</td>
                                            <td>{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</td>
                                            <td>{{ $log->user_id }}</td>
                                            <td>{{ $log->accion }}</td>
                                            <td>{{ $log->entidad }}</td>
                                            <td>{{ $log->entidad_id }}</td>
                                            <td>{{ $log->ip }}</td>
                                            <td><pre class="mb-0" style="white-space: pre-wrap;">{{ json_encode($log->valores_antes, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre></td>
                                            <td><pre class="mb-0" style="white-space: pre-wrap;">{{ json_encode($log->valores_despues, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                {!! $logs->links() !!}
            </div>
        </div>
    </div>
@endsection
