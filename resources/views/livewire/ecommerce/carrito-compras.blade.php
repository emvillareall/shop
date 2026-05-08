<div class="space-y-4" wire:poll.1s="tickReserva">
    @if(count($items))
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
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
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $i => $item)
                        <tr>
                            <td class="font-medium">{{ $item['descripcion'] }}</td>
                            <td>{{ $item['color'] }}</td>
                            <td>{{ $item['talla'] }}</td>
                            <td class="w-36">
                                <input type="number"
                                       min="1"
                                       value="{{ $item['cantidad'] }}"
                                       wire:change="updateCantidad({{ $i }}, $event.target.value)">
                            </td>
                            <td>${{ number_format($item['precio'], 2) }}</td>
                            <td class="font-semibold">${{ number_format($item['precio'] * $item['cantidad'], 2) }}</td>
                            <td>
                                <button wire:click="removeItem({{ $i }})" class="btn btn-danger btn-sm">Quitar</button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <p class="text-lg font-bold text-slate-900">Total: ${{ number_format($this->total, 2) }}</p>
                <div class="flex flex-wrap gap-2">
                    <button wire:click="clear" class="btn btn-secondary btn-sm">Vaciar carrito</button>
                    <a href="{{ route('ecommerce.checkout.index') }}" class="btn btn-primary btn-sm">Continuar al checkout</a>
                </div>
            </div>
            <p class="mt-2 text-sm font-semibold text-amber-700">
                Reserva activa:
                {{ str_pad((string) floor($secondsRemaining / 60), 2, '0', STR_PAD_LEFT) }}:{{ str_pad((string) ($secondsRemaining % 60), 2, '0', STR_PAD_LEFT) }}
            </p>
            <p class="mt-2 text-xs text-slate-500">Los cambios de cantidad se guardan al instante y respetan stock por variante.</p>
        </div>
    @else
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <p class="text-slate-600">Tu carrito esta vacio.</p>
            <a href="{{ route('ecommerce.productos.index') }}" class="btn btn-primary btn-sm mt-4">Ver catalogo</a>
        </div>
    @endif
</div>
