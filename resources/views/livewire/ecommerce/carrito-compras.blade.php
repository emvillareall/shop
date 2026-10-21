<div class="min-w-0 space-y-4" wire:poll.1s="tickReserva">
    @if($errors->has('carrito'))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800 sm:px-4 sm:py-3">
            {{ $errors->first('carrito') }}
        </div>
    @endif

    @if(count($items))
        {{-- Mobile product cards: keep each variant readable without a compressed table. --}}
        <div class="space-y-3 lg:hidden">
            @foreach($items as $i => $item)
                @php($imageKey = ((int) ($item['producto_id'] ?? 0)) . ':' . ((int) ($item['color_id'] ?? 0)))
                <article class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex min-w-0 gap-3 p-3 sm:gap-4 sm:p-4">
                        <div class="h-24 w-20 shrink-0 overflow-hidden rounded-lg bg-slate-100 ring-1 ring-inset ring-slate-200">
                            @if(!empty($productImages[$imageKey] ?? null))
                                <img src="{{ $productImages[$imageKey] }}" alt="{{ $item['descripcion'] }}" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <div role="img" aria-label="Imagen no disponible: {{ $item['descripcion'] }}" class="flex h-full w-full items-center justify-center text-slate-400">
                                    <svg aria-hidden="true" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m4 17 5-5 3 3 2-2 6 6"/></svg>
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex min-w-0 items-start justify-between gap-2">
                                <h2 class="min-w-0 break-words text-sm font-semibold leading-5 text-slate-900 sm:text-base">{{ $item['descripcion'] }}</h2>
                                <button type="button" wire:click="removeItem({{ $i }})" aria-label="Quitar {{ $item['descripcion'] }} del carrito" class="inline-flex min-h-10 min-w-10 shrink-0 items-center justify-center rounded-lg text-rose-600 transition hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300" title="Quitar del carrito">
                                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                </button>
                            </div>

                            <p class="mt-0.5 break-words text-xs text-slate-500">Color: <span class="font-medium text-slate-700">{{ $item['color'] }}</span></p>

                            <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px]">
                                <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-600">Talla: <strong class="text-slate-800">{{ $item['talla'] }}</strong></span>
                                <label class="inline-flex min-h-8 items-center gap-1.5 rounded-md bg-slate-100 pl-2 text-slate-600" for="cart-quantity-mobile-{{ $i }}">
                                    Cantidad
                                    <input id="cart-quantity-mobile-{{ $i }}" type="number" min="1" value="{{ $item['cantidad'] }}" aria-label="Cantidad de {{ $item['descripcion'] }}" wire:change="updateCantidad({{ $i }}, $event.target.value)" class="!w-[4.25rem] rounded-md border-slate-300 px-1.5 py-1 text-center text-sm font-semibold text-slate-800 focus:border-brand-500 focus:ring-brand-200">
                                </label>
                            </div>

                            <div class="mt-2 flex items-end justify-between gap-2 border-t border-slate-100 pt-2">
                                <span class="text-xs text-slate-500">Subtotal del producto</span>
                                <span class="shrink-0 text-base font-bold text-brand-800">${{ number_format($item['precio'] * $item['cantidad'], 2) }}</span>
                            </div>
                            <p class="mt-0.5 text-[10px] text-slate-500">Precio unitario: ${{ number_format($item['precio'], 2) }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- The wider layout retains a scan-friendly table and adds the same color-aware thumbnail. --}}
        <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:block">
            <div class="overflow-x-auto">
                <table class="table min-w-[760px]">
                    <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Color</th>
                        <th>Talla</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                        <th><span class="sr-only">Acciones</span></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $i => $item)
                        @php($imageKey = ((int) ($item['producto_id'] ?? 0)) . ':' . ((int) ($item['color_id'] ?? 0)))
                        <tr>
                            <td class="min-w-[18rem]">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="h-16 w-14 shrink-0 overflow-hidden rounded-lg bg-slate-100 ring-1 ring-inset ring-slate-200">
                                        @if(!empty($productImages[$imageKey] ?? null))
                                            <img src="{{ $productImages[$imageKey] }}" alt="{{ $item['descripcion'] }}" loading="lazy" class="h-full w-full object-cover">
                                        @else
                                            <div role="img" aria-label="Imagen no disponible: {{ $item['descripcion'] }}" class="flex h-full w-full items-center justify-center text-slate-400">
                                                <svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m4 17 5-5 3 3 2-2 6 6"/></svg>
                                            </div>
                                        @endif
                                    </div>
                                    <span class="min-w-0 break-words font-semibold text-slate-800">{{ $item['descripcion'] }}</span>
                                </div>
                            </td>
                            <td class="break-words">{{ $item['color'] }}</td>
                            <td class="break-words">{{ $item['talla'] }}</td>
                            <td class="w-36">
                                <input type="number" min="1" value="{{ $item['cantidad'] }}" aria-label="Cantidad de {{ $item['descripcion'] }}" wire:change="updateCantidad({{ $i }}, $event.target.value)" class="!w-24 rounded-lg border-slate-300 px-2 py-1.5 text-center focus:border-brand-500 focus:ring-brand-200">
                            </td>
                            <td class="whitespace-nowrap">${{ number_format($item['precio'], 2) }}</td>
                            <td class="whitespace-nowrap font-semibold text-slate-900">${{ number_format($item['precio'] * $item['cantidad'], 2) }}</td>
                            <td>
                                <button type="button" wire:click="removeItem({{ $i }})" aria-label="Quitar {{ $item['descripcion'] }} del carrito" class="inline-flex min-h-10 items-center justify-center rounded-lg px-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300">Quitar</button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <section class="rounded-xl border border-brand-100 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total del carrito</p>
                    <p class="mt-0.5 text-2xl font-bold tracking-tight text-brand-800">${{ number_format($this->total, 2) }}</p>
                </div>
                <p class="inline-flex min-h-9 w-fit items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200">
                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm.75 4a.75.75 0 0 0-1.5 0v4c0 .2.08.39.22.53l2.5 2.5a.75.75 0 1 0 1.06-1.06l-2.28-2.28V6Z" clip-rule="evenodd"/></svg>
                    Reserva activa:
                    {{ str_pad((string) floor($secondsRemaining / 60), 2, '0', STR_PAD_LEFT) }}:{{ str_pad((string) ($secondsRemaining % 60), 2, '0', STR_PAD_LEFT) }}
                </p>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 sm:justify-end lg:flex">
                <a href="{{ route('ecommerce.checkout.index') }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-brand-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-200 focus-visible:ring-offset-2 lg:w-auto">
                    Continuar al checkout
                </a>
                <button type="button" wire:click="clear" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-slate-200 focus-visible:ring-offset-2 lg:w-auto">
                    Vaciar carrito
                </button>
            </div>
            <p class="mt-3 text-xs leading-5 text-slate-500">Los cambios de cantidad se guardan al instante y respetan stock por variante.</p>
        </section>
    @else
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-10 text-center shadow-sm sm:py-12">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                <svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 1.9-1.4L21 8H6"/><circle cx="10" cy="19" r="1.4"/><circle cx="18" cy="19" r="1.4"/></svg>
            </span>
            <p class="mt-3 font-semibold text-slate-800">Tu carrito está vacío.</p>
            <p class="mt-1 text-sm text-slate-500">Explora el catálogo para encontrar tu próximo conjunto favorito.</p>
            <a href="{{ route('ecommerce.productos.index') }}" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-200 focus-visible:ring-offset-2 sm:w-auto">Ver catálogo</a>
        </div>
    @endif
</div>
