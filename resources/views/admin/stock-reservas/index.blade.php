@extends('layouts.app')

@section('content')
    <div class="space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">Presión de compra en tiempo real</h1>
            <p class="mt-1 text-sm text-slate-600">Reservas activas por variante para monitorear concurrencia y riesgo de agotamiento.</p>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Líneas activas</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ (int) ($resumen->lineas_activas ?? 0) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Unidades reservadas</p>
                <p class="mt-1 text-2xl font-bold text-brand-700">{{ (int) ($resumen->unidades_reservadas ?? 0) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Sesiones activas</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ (int) ($resumen->sesiones_activas ?? 0) }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="table min-w-[900px]">
                    <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Color</th>
                        <th>Talla</th>
                        <th>Stock actual</th>
                        <th>Reservado</th>
                        <th>Sesiones</th>
                        <th>Primer vencimiento</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($porVariante as $row)
                        @php
                            $stock = (int) $row->stock_actual;
                            $reservado = (int) $row->unidades_reservadas;
                            $ratio = $stock > 0 ? ($reservado / $stock) : 1;
                        @endphp
                        <tr>
                            <td class="font-medium">{{ $row->producto }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="inline-block h-3 w-3 rounded-full border border-slate-300" style="background-color: {{ $row->codigo_color ?? '#94a3b8' }}"></span>
                                    <span>{{ $row->nombre_color }}</span>
                                </div>
                            </td>
                            <td>{{ $row->talla }}</td>
                            <td>{{ $stock }}</td>
                            <td class="font-semibold {{ $ratio >= 0.8 ? 'text-rose-600' : ($ratio >= 0.5 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $reservado }}</td>
                            <td>{{ (int) $row->sesiones }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->primer_expira)->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-slate-500">No hay reservas activas en este momento.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 p-3">
                {{ $porVariante->links() }}
            </div>
        </div>
    </div>
@endsection

