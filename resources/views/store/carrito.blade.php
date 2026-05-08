@extends('layouts.app_cliente')

@section('content')
<div class="mx-auto max-w-6xl space-y-6 px-4 py-6" x-data="carritoCheckout()">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Carrito de compras</h1>
        <a href="{{ url('/tienda') }}" class="btn btn-secondary btn-sm">Seguir comprando</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(count($carrito))
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Color</th>
                            <th>Talla</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                            <th>Accion</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $total = 0; @endphp
                        @foreach($carrito as $item)
                            <tr>
                                <td>{{ $item['descripcion'] }}</td>
                                <td>{{ $item['color'] ?? '-' }}</td>
                                <td>{{ $item['talla'] ?? '-' }}</td>
                                <td>{{ $item['cantidad'] }}</td>
                                <td>${{ number_format($item['precio'], 2) }}</td>
                                <td>${{ number_format($item['precio'] * $item['cantidad'], 2) }}</td>
                                <td>
                                    <form method="POST" action="{{ route('ecommerce.carrito.eliminar', ['index' => $loop->index]) }}" onsubmit="return confirm('Eliminar este producto del carrito?');">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            @php $total += $item['precio'] * $item['cantidad']; @endphp
                        @endforeach
                        <tr>
                            <td colspan="5" class="text-right font-semibold">Total</td>
                            <td class="font-semibold">${{ number_format($total, 2) }}</td>
                            <td></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="text-sm font-semibold">Cliente</h2>
            </div>
            <div class="card-body space-y-4">
                <div class="grid gap-2 sm:grid-cols-[1fr_auto]">
                    <input type="text" x-model="cedulaBuscar" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Ingrese cedula...">
                    <button type="button" class="btn btn-primary btn-sm" @click="buscarCliente">Buscar</button>
                </div>

                <form method="POST" action="{{ route('carrito.guardar') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="cliente_id" :value="clienteId" required>

                    <div x-show="clienteId" x-cloak class="rounded-md border border-slate-200 bg-slate-50 p-3 text-sm">
                        <p><span class="font-medium">Nombre:</span> <span x-text="cliente.nombre"></span></p>
                        <p><span class="font-medium">Telefono:</span> <span x-text="cliente.telefono"></span></p>
                        <p><span class="font-medium">Ciudad:</span> <span x-text="cliente.ciudad"></span></p>
                        <p><span class="font-medium">Direccion:</span> <span x-text="cliente.direccion"></span></p>
                        <p><span class="font-medium">Email:</span> <span x-text="cliente.email"></span></p>
                    </div>

                    <button x-show="clienteId" x-cloak class="btn btn-primary btn-sm">Finalizar pedido</button>
                </form>

                <form method="POST" action="{{ route('ecommerce.carrito.vaciar') }}" onsubmit="return confirm('Estas seguro de que deseas vaciar el carrito?');">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">Vaciar carrito</button>
                </form>
            </div>
        </div>
    @else
        <div class="alert alert-info">No hay productos en el carrito.</div>
    @endif

    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="modalOpen = false">
        <form class="w-full max-w-lg rounded-lg bg-white p-4 shadow-xl space-y-3" @submit.prevent="registrarCliente">
            <h3 class="text-base font-semibold">Registro rapido</h3>
            <p class="text-sm text-slate-600">Completa tus datos para continuar con el pedido.</p>
            <input type="text" x-model="nuevo.nombres_clientes" placeholder="Nombres" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
            <input type="text" x-model="nuevo.apellidos_clientes" placeholder="Apellidos" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
            <input type="text" x-model="nuevo.cedula_clientes" class="w-full rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-sm" readonly>
            <input type="text" x-model="nuevo.telefono_clientes" placeholder="Telefono" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
            <input type="text" x-model="nuevo.ciudad_clientes" placeholder="Ciudad" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
            <input type="text" x-model="nuevo.direccion_clientes" placeholder="Direccion" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
            <input type="email" x-model="nuevo.email_clientes" placeholder="Email" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Registrar y seleccionar</button>
                <button type="button" class="btn btn-secondary btn-sm" @click="modalOpen = false">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function carritoCheckout() {
    return {
        cedulaBuscar: '',
        modalOpen: false,
        clienteId: '',
        cliente: { nombre: '', telefono: '', ciudad: '', direccion: '', email: '' },
        nuevo: {
            nombres_clientes: '',
            apellidos_clientes: '',
            cedula_clientes: '',
            telefono_clientes: '',
            ciudad_clientes: '',
            direccion_clientes: '',
            email_clientes: ''
        },
        async buscarCliente() {
            if (!this.cedulaBuscar) {
                alert('Ingrese una cedula valida.');
                return;
            }
            try {
                const res = await fetch(`/clientes/buscar/${this.cedulaBuscar}`);
                if (!res.ok) throw new Error();
                const data = await res.json();
                if (data.exists) {
                    this.clienteId = data.id;
                    this.cliente = {
                        nombre: data.nombre,
                        telefono: data.telefono,
                        ciudad: data.ciudad,
                        direccion: data.direccion,
                        email: data.email
                    };
                } else {
                    this.nuevo.cedula_clientes = this.cedulaBuscar;
                    this.modalOpen = true;
                }
            } catch (e) {
                alert('No se pudo buscar el cliente.');
            }
        },
        async registrarCliente() {
            try {
                const payload = new FormData();
                payload.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                Object.entries(this.nuevo).forEach(([k, v]) => payload.append(k, v));
                const res = await fetch('/clientes/registrar', { method: 'POST', body: payload });
                if (!res.ok) throw new Error();
                const data = await res.json();
                if (!data.success) throw new Error();
                this.clienteId = data.cliente.id;
                this.cliente = {
                    nombre: `${data.cliente.nombres_clientes} ${data.cliente.apellidos_clientes}`,
                    telefono: data.cliente.telefono_clientes,
                    ciudad: data.cliente.ciudad_clientes,
                    direccion: data.cliente.direccion_clientes,
                    email: data.cliente.email_clientes
                };
                this.modalOpen = false;
            } catch (e) {
                alert('No se pudo registrar el cliente.');
            }
        }
    }
}
</script>
@endsection

