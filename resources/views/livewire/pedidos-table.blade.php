<div x-data="{ pagoModalOpen: false, pagoPedidoCodigo: '', pagoRuta: '' }" class="space-y-4">
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('pedidos.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="min-w-[240px] flex-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Buscar</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cliente, tienda o descripcion, cedula, codigo o ID">
                </div>
                <div class="min-w-[180px] flex-1 sm:flex-none sm:w-52">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Estado URL</label>
                    <select name="estadoUrl">
                        <option value="">Todos</option>
                        <option value="EN ESPERA" @selected($estadoUrl === 'EN ESPERA')>EN ESPERA</option>
                        <option value="ENVIADO" @selected($estadoUrl === 'ENVIADO')>ENVIADO</option>
                    </select>
                </div>
                <div class="min-w-[210px] flex-1 sm:flex-none sm:w-56">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Estado pedido</label>
                    <select name="estadoPedido">
                        <option value="">Todos</option>
                        @foreach(['BORRADOR','PENDIENTE_PAGO','PAGO_EN_REVISION','PAGADO','EN_PREPARACION','DESPACHADO','ENTREGADO','CANCELADO','RECHAZADO','DEVUELTO'] as $ep)
                            <option value="{{ $ep }}" @selected($estadoPedido === $ep)>{{ $ep }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[180px] flex-1 sm:flex-none sm:w-52">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Estado pago</label>
                    <select name="estadoPago">
                        <option value="">Todos</option>
                        @foreach(['SIN_PAGO','PENDIENTE','EN_REVISION','APROBADO','RECHAZADO','REEMBOLSADO'] as $ep)
                            <option value="{{ $ep }}" @selected($estadoPago === $ep)>{{ $ep }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[180px] flex-1 sm:flex-none sm:w-52">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Estado envio</label>
                    <select name="estadoEnvio">
                        <option value="">Todos</option>
                        @foreach(['SIN_ENVIO','PENDIENTE','PREPARANDO','ENVIADO','ENTREGADO','NO_ENTREGADO'] as $ee)
                            <option value="{{ $ee }}" @selected($estadoEnvio === $ee)>{{ $ee }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[120px] flex-1 sm:flex-none sm:w-36">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Mostrar</label>
                    <select name="perPage">
                        <option value="10" @selected((int)$perPage === 10)>10</option>
                        <option value="20" @selected((int)$perPage === 20)>20</option>
                        <option value="30" @selected((int)$perPage === 30)>30</option>
                        <option value="50" @selected((int)$perPage === 50)>50</option>
                    </select>
                </div>
                <div class="flex h-[42px] items-center rounded-xl border border-slate-200 bg-slate-50 px-3">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="todayOnly" value="1" @checked($todayOnly) class="h-4 w-4 rounded border-slate-300 text-brand-600">
                        Solo hoy
                    </label>
                </div>
                <div class="flex h-[42px] items-center gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Aplicar</button>
                    <a href="{{ route('pedidos.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
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
                        <th>Descripcion / Origen</th>
                        <th>Cliente</th>
                        <th>Tienda</th>
                        <th>Subtotal</th>
                        <th>Descuento</th>
                        <th>Total</th>
                        <th>Productos</th>
                        <th>Estado pago</th>
                        <th>Estado envio</th>
                        <th class="bg-brand-50 text-brand-800">Estado pedido</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($pedidos as $pedido)
                        @php
                            $isHighlighted = $highlightPedidoId && (int)$highlightPedidoId === (int)$pedido->id;
                            $phone = preg_replace('/\D+/', '', (string) $pedido->telefono_clientes);
                            if (str_starts_with($phone, '09') && strlen($phone) === 10) { $phone = '593'.substr($phone, 1); }
                            elseif (str_starts_with($phone, '9') && strlen($phone) === 9) { $phone = '593'.$phone; }

                            $estadoPedido = $pedido->estado_pedido ?: ($pedido->estado_url === 'ENVIADO' ? 'DESPACHADO' : 'PENDIENTE_PAGO');
                            $estadoPago = $pedido->estado_pago ?: 'SIN_PAGO';
                            $estadoEnvio = $pedido->estado_envio ?: ($pedido->estado_url === 'ENVIADO' ? 'ENVIADO' : 'PENDIENTE');
                            $esPos = str_starts_with((string)($pedido->codigo_pedido ?? ''), 'BF-POS-');
                            $esSocial = str_starts_with((string)($pedido->codigo_pedido ?? ''), 'BF-ADM-')
                                || !str_contains(strtolower((string)($pedido->descripcion ?? '')), 'en linea');
                            $tienePagoEcommerce = !empty($pedido->pago_registrado_estado);
                            $labelsPedido = \App\Models\Pedido::ESTADO_PEDIDO_LABELS;
                            $labelsPago = \App\Models\Pedido::ESTADO_PAGO_LABELS;
                            $labelsEnvio = \App\Models\Pedido::ESTADO_ENVIO_LABELS;
                            $pedidoBadge = match ($estadoPedido) {
                                'PAGADO', 'ENTREGADO' => 'bg-success',
                                'PENDIENTE_PAGO', 'PAGO_EN_REVISION', 'EN_PREPARACION' => 'bg-warning',
                                'CANCELADO', 'RECHAZADO' => 'bg-danger',
                                default => 'bg-primary',
                            };
                            $pagoBadge = match ($estadoPago) {
                                'APROBADO' => 'bg-success',
                                'EN_REVISION', 'PENDIENTE' => 'bg-warning',
                                'RECHAZADO' => 'bg-danger',
                                default => 'bg-secondary',
                            };
                            $envioBadge = match ($estadoEnvio) {
                                'ENTREGADO', 'ENVIADO' => 'bg-success',
                                'PENDIENTE', 'PREPARANDO' => 'bg-warning',
                                'NO_ENTREGADO' => 'bg-danger',
                                default => 'bg-info',
                            };
                            $pagoConfirmado = (($pedido->estado_pago ?? 'SIN_PAGO') === 'APROBADO');
                            $tieneDetalles = ((int)($pedido->detalles_count ?? 0) > 0);
                        @endphp
                        <tr class="{{ $pedido->clientes_id == 1 ? 'bg-slate-50' : '' }}">
                            <td>{{ $pedidos->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="font-medium">{{ $pedido->descripcion }}</div>
                                @if($esPos)
                                    <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-800 ring-1 ring-emerald-200">
                                        <i class="fa-solid fa-cash-register text-[10px]"></i>
                                        Venta mostrador
                                    </span>
                                @elseif($esSocial)
                                    <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-sky-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-sky-800 ring-1 ring-sky-200">
                                        <i class="fa-solid fa-hashtag text-[10px]"></i>
                                        Redes sociales
                                    </span>
                                @else
                                    <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-violet-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-violet-800 ring-1 ring-violet-200">
                                        <i class="fa-solid fa-store text-[10px]"></i>
                                        Ecommerce
                                    </span>
                                @endif
                            </td>
                            <td>{{ $pedido->nombres_clientes }} {{ $pedido->apellidos_clientes }}</td>
                            <td>{{ $pedido->nombre_tienda }}</td>
                            <td>${{ number_format((float) $pedido->subtotal_pedido, 2) }}</td>
                            <td>${{ number_format((float) $pedido->descuentos_pedido, 2) }}</td>
                            <td>${{ number_format((float) $pedido->total_pedido, 2) }}</td>
                            <td>
                                @if((int)($pedido->detalles_count ?? 0) > 0)
                                    <span class="badge bg-success">Asignados ({{ (int)$pedido->detalles_count }})</span>
                                @else
                                    <span class="badge bg-secondary">Sin productos</span>
                                @endif
                                <div class="mt-2">
                                    <a href="{{ route('detalle-pedidos.create', ['id' => $pedido->id, 'return_to' => url()->current()]) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="fa fa-box-open"></i>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="badge {{ $pagoBadge }}">{{ $labelsPago[$estadoPago] ?? str_replace('_', ' ', $estadoPago) }}</span>
                                    @if($esSocial && $estadoPago !== 'APROBADO')
                                        <button type="button" class="btn btn-sm btn-warning"
                                                title="Confirmar pago"
                                                @click="pagoModalOpen = true; pagoPedidoCodigo='{{ addslashes($pedido->codigo_pedido ?: ('#'.$pedido->id)) }}'; pagoRuta='{{ route('pedidos.confirmar_pago_social', $pedido->id) }}'">
                                            <i class="fa-solid fa-money-check-dollar"></i>
                                        </button>
                                    @elseif(!$esSocial && $tienePagoEcommerce)
                                        <span class="text-xs text-slate-500">Gestionado en Pagos</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="badge {{ $envioBadge }}">{{ $labelsEnvio[$estadoEnvio] ?? str_replace('_', ' ', $estadoEnvio) }}</span>
                                    @if(($pedido->estado_envio ?? null) !== 'ENVIADO' && $pagoConfirmado && $tieneDetalles)
                                        <form method="POST" action="{{ route('estado_pedido', $pedido->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-info" title="Marcar como enviado">
                                                <i class="fa fa-paper-plane"></i>
                                            </button>
                                        </form>
                                    @elseif(($pedido->estado_envio ?? null) !== 'ENVIADO')
                                        <span class="text-xs text-slate-400" title="Disponible al confirmar pago y tener productos asignados">Bloqueado</span>
                                    @endif
                                </div>
                            </td>
                            <td class="bg-brand-50/60">
                                <span class="badge {{ $pedidoBadge }} !px-3 !py-1.5 !text-xs !font-bold !tracking-wide !shadow-sm">
                                    {{ $labelsPedido[$estadoPedido] ?? str_replace('_', ' ', $estadoPedido) }}
                                </span>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @if($isHighlighted && $urlSigned)
                                        <button type="button" class="btn btn-sm btn-dark" title="Copiar link" onclick="navigator.clipboard.writeText('{{ $urlSigned }}')"><i class="fa-solid fa-copy"></i></button>
                                    @endif
                                    @if($isHighlighted && $urlSigned && str_starts_with($phone, '593') && strlen($phone) === 12)
                                        @php $message = rawurlencode("Hola\n\nTe compartimos el enlace seguro para completar los datos de tu pedido:\n".$urlSigned); $waUrl = "https://wa.me/{$phone}?text={$message}"; @endphp
                                        <a class="btn btn-sm btn-success" target="_blank" href="{{ $waUrl }}"><i class="fa-brands fa-whatsapp"></i></a>
                                    @endif
                                    <a class="btn btn-sm btn-success" href="{{ route('pedidos.edit',$pedido->id) }}"><i class="fa fa-fw fa-edit"></i></a>
                                    <a class="btn btn-sm btn-warning" href="{{ route('getPDF_pedidos',$pedido->id) }}" target="_blank"><i class="fa fa-fw fa-print"></i></a>
                                    <a href="{{ route('detalle-pedidos.show', $pedido->id) }}" class="btn btn-sm btn-dark"><i class="fa fa-eye"></i></a>
                                    <form action="{{ route('pedidos.destroy',$pedido->id) }}" method="POST" onsubmit="return confirm('¿Eliminar pedido y restaurar stock?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-fw fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="py-8 text-center text-slate-500">No hay pedidos para los filtros seleccionados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $pedidos->links() }}</div>
        </div>
    </div>

    <div x-show="pagoModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-slate-900/60" @click="pagoModalOpen = false"></div>
        <div class="relative z-10 w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Confirmar pago (redes sociales)</h3>
                <button type="button" class="btn btn-sm" @click="pagoModalOpen = false">Cerrar</button>
            </div>
            <p class="mb-3 text-sm text-slate-600">Pedido: <span class="font-semibold" x-text="pagoPedidoCodigo"></span></p>
            <form method="POST" :action="pagoRuta" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Numero de transaccion / referencia</label>
                    <input type="text" name="numero_referencia" placeholder="Ej: TRX-12345">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Comprobante (opcional)</label>
                    <input type="file" name="comprobante" accept=".jpg,.jpeg,.png,.pdf">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Observacion (opcional)</label>
                    <textarea name="observacion" rows="3" placeholder="Detalle breve"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button" class="btn btn-sm" @click="pagoModalOpen = false">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">Confirmar pago realizado</button>
                </div>
            </form>
        </div>
    </div>
</div>
