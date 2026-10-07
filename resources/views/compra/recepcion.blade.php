@extends('layouts.app')

@section('template_title')
    Recepción de compra #{{ $compra->id }}
@endsection

@section('content')
    @php
        $estadoRecepcion = (string) $compra->estado_recepcion;
        $estadoLabel = [
            'EN_TRANSITO' => 'EN TRÁNSITO',
            'RECEPCION_PARCIAL' => 'RECEPCIÓN PARCIAL',
        ][$estadoRecepcion] ?? $estadoRecepcion;
        $estadoClass = $estadoRecepcion === 'RECEPCION_PARCIAL'
            ? 'bg-amber-100 text-amber-800'
            : 'bg-sky-100 text-sky-800';
    @endphp

    <div class="mx-auto max-w-7xl space-y-5">
        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-start lg:justify-between">
            <div>
                <a href="{{ route('compras.show', $compra) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Volver a la compra
                </a>
                <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900">Recepción de compra #{{ $compra->id }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $compra->codigo_compra }} · {{ $compra->descripcion_compra }}</p>
            </div>
            <span class="self-start rounded-full px-3 py-1 text-xs font-bold {{ $estadoClass }}">{{ $estadoLabel }}</span>
        </div>

        @if(session('danger'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">
                {{ session('danger') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">
                <p class="font-bold">Revisa los datos de la recepción:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Proveedor</p>
                <p class="mt-1 font-bold text-slate-900">{{ $compra->proveedor?->tienda_proveedor ?? 'No especificado' }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de compra</p>
                <p class="mt-1 font-bold text-slate-900">{{ optional($compra->created_at)->format('d/m/Y H:i') ?? '—' }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total compra</p>
                <p class="mt-1 font-bold text-slate-900">${{ number_format((float) ($compra->total_final_compra ?? 0), 2) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Líneas pendientes</p>
                <p class="mt-1 font-bold text-slate-900">{{ $itemsPendientes->count() }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
            <p class="font-bold">Registra únicamente lo que llegó físicamente.</p>
            <p class="mt-1">Las unidades aceptadas entran al inventario. Las dañadas, incorrectas y faltantes quedan registradas como incidencia.</p>
        </div>

        <form method="POST" action="{{ route('compras.recepciones.store', $compra) }}" data-reception-form>
            @csrf
            <input type="hidden" name="modo" value="manual">
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">

            <div class="space-y-4">
                @forelse($itemsPendientes as $item)
                    @php
                        $itemKey = 'items.' . $item->id;
                        $cierra = filter_var(old($itemKey . '.cierra_item', false), FILTER_VALIDATE_BOOLEAN);
                    @endphp
                    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                        <input type="hidden" name="items[{{ $item->id }}][compra_item_id]" value="{{ $item->id }}">

                        <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Compra item #{{ $item->id }}</p>
                                <h2 class="mt-1 text-lg font-bold text-slate-900">
                                    {{ $item->descripcion_producto ?? ('Producto #' . $item->producto_id) }}
                                </h2>
                                <p class="text-sm text-slate-600">
                                    {{ $item->nombre_color ?? ('Color #' . ($item->colores_id ?? '—')) }} · Talla {{ $item->talla ?: '—' }}
                                </p>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center text-xs sm:min-w-[300px]">
                                <div class="rounded-lg bg-slate-50 px-2 py-2">
                                    <span class="block text-slate-500">Comprado</span>
                                    <strong class="text-base text-slate-900">{{ $item->cantidad }}</strong>
                                </div>
                                <div class="rounded-lg bg-emerald-50 px-2 py-2">
                                    <span class="block text-emerald-700">Aceptado acum.</span>
                                    <strong class="text-base text-emerald-800">{{ $item->aceptada_acumulada }}</strong>
                                </div>
                                <div class="rounded-lg bg-amber-50 px-2 py-2">
                                    <span class="block text-amber-700">Pendiente</span>
                                    <strong class="text-base text-amber-800">{{ $item->pendiente_recepcion }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label for="accepted-{{ $item->id }}" class="block text-sm font-semibold text-slate-700">Buenas / aceptadas</label>
                                <input id="accepted-{{ $item->id }}" type="number" min="0" step="1" inputmode="numeric"
                                    name="items[{{ $item->id }}][cantidad_aceptada]"
                                    value="{{ old($itemKey . '.cantidad_aceptada', 0) }}"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <p class="mt-1 text-xs text-slate-500">Entran al inventario.</p>
                            </div>
                            <div>
                                <label for="damaged-{{ $item->id }}" class="block text-sm font-semibold text-slate-700">Dañadas</label>
                                <input id="damaged-{{ $item->id }}" type="number" min="0" step="1" inputmode="numeric"
                                    name="items[{{ $item->id }}][cantidad_danada]"
                                    value="{{ old($itemKey . '.cantidad_danada', 0) }}"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <p class="mt-1 text-xs text-slate-500">No ingresan al inventario.</p>
                            </div>
                            <div>
                                <label for="incorrect-{{ $item->id }}" class="block text-sm font-semibold text-slate-700">Incorrectas</label>
                                <input id="incorrect-{{ $item->id }}" type="number" min="0" step="1" inputmode="numeric"
                                    name="items[{{ $item->id }}][cantidad_incorrecta]"
                                    value="{{ old($itemKey . '.cantidad_incorrecta', 0) }}"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <p class="mt-1 text-xs text-slate-500">No ingresan al inventario.</p>
                            </div>
                            <div>
                                <label for="missing-{{ $item->id }}" class="block text-sm font-semibold text-slate-700">Faltantes definitivas</label>
                                <input id="missing-{{ $item->id }}" type="number" min="0" step="1" inputmode="numeric"
                                    name="items[{{ $item->id }}][cantidad_faltante_declarada]"
                                    value="{{ old($itemKey . '.cantidad_faltante_declarada', 0) }}"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <p class="mt-1 text-xs text-slate-500">El proveedor no las entregó.</p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 rounded-lg bg-slate-50 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input type="hidden" name="items[{{ $item->id }}][cierra_item]" value="0">
                                <input type="checkbox" name="items[{{ $item->id }}][cierra_item]" value="1" {{ $cierra ? 'checked' : '' }}
                                    class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                Cerrar línea: no se esperan más unidades de esta variante.
                            </label>
                            <span class="text-xs text-slate-500">Recibido físicamente acumulado: {{ $item->fisicamente_contabilizada }}</span>
                        </div>
                    </section>
                @empty
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                        No hay cantidades pendientes para registrar en esta compra.
                    </div>
                @endforelse
            </div>

            @if($itemsPendientes->isNotEmpty())
                <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <label for="observacion" class="block text-sm font-semibold text-slate-700">Observación general <span class="font-normal text-slate-400">(opcional)</span></label>
                    <textarea id="observacion" name="observacion" rows="3" maxlength="1000"
                        class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Ej. Entrega parcial del proveedor.">{{ old('observacion') }}</textarea>
                    <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <a href="{{ route('compras.show', $compra) }}" class="btn btn-secondary text-center">Cancelar</a>
                        <button type="submit" class="btn btn-primary" data-submit-reception>
                            <i class="fa-solid fa-check mr-1"></i> Confirmar recepción
                        </button>
                    </div>
                </div>
            @endif
        </form>

        @if($itemsPendientes->isNotEmpty())
            <form method="POST" action="{{ route('compras.recepciones.store', $compra) }}" class="rounded-xl border border-amber-200 bg-amber-50 p-4" onsubmit="return confirm('Se registrará todo lo pendiente como recibido correctamente y entrará al inventario. ¿Deseas continuar?')" data-reception-form>
                @csrf
                <input type="hidden" name="modo" value="todo">
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-bold text-amber-900">Recepción completa rápida</p>
                        <p class="text-sm text-amber-800">Usa esta opción solo si todas las unidades pendientes llegaron buenas.</p>
                    </div>
                    <button type="submit" class="btn btn-warning whitespace-nowrap" data-submit-reception>
                        <i class="fa-solid fa-boxes-stacked mr-1"></i> Recibir todo correctamente
                    </button>
                </div>
            </form>
        @endif

        @if($recepciones->isNotEmpty())
            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Historial de recepciones</h2>
                <div class="mt-3 space-y-3">
                    @foreach($recepciones as $recepcion)
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-3 text-sm">
                            <p class="font-semibold text-slate-800">{{ optional($recepcion->fecha_recepcion)->format('d/m/Y H:i') }} · {{ $recepcion->user?->name ?? 'Usuario no identificado' }}</p>
                            @if($recepcion->observacion)
                                <p class="mt-1 text-slate-600">{{ $recepcion->observacion }}</p>
                            @endif
                            <p class="mt-1 text-xs text-slate-500">{{ $recepcion->items->count() }} línea(s) registrada(s).</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-reception-form]').forEach(function (form) {
                form.addEventListener('submit', function () {
                    form.querySelectorAll('[data-submit-reception]').forEach(function (button) {
                        button.disabled = true;
                        button.classList.add('opacity-60', 'cursor-not-allowed');
                    });
                });
            });
        });
    </script>
@endsection
