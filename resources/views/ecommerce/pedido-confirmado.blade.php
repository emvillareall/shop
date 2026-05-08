@extends('layouts.ecommerce', ['title' => 'Pedido confirmado | Booty Fitness'])

@section('content')
    @php
        $estadoPagoClass = $pedidoModel->estadoPagoBadgeClass();
        $estadoPedidoClass = $pedidoModel->estadoPedidoBadgeClass();
        $metodo = $pago?->metodo;
        $estadoPagoProveedor = strtoupper((string) ($pago->estado ?? $pedidoModel->estadoPagoNormalizado()));
        $providerConfirmed = (bool) data_get((array)($pago->metadata ?? []), 'provider_confirmed', false);
    @endphp

    <div class="mx-auto max-w-3xl rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm md:p-8">
        <div class="text-center">
            <x-ecommerce.badge variant="success">Pedido registrado</x-ecommerce.badge>
            <h1 class="mt-3 text-3xl font-bold text-slate-900">Gracias por tu compra</h1>
            <p class="mt-2 text-slate-600">Tu pedido #{{ $pedidoId }} fue recibido correctamente.</p>
        </div>

        <div class="mt-5 grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm md:grid-cols-2">
            <p><span class="font-semibold">Codigo:</span> {{ $pedidoModel->codigo_pedido ?? ('PED-' . $pedidoId) }}</p>
            <p><span class="font-semibold">Metodo:</span> {{ $pago ? strtoupper($pago->metodo) : 'N/A' }}</p>
            <p>
                <span class="font-semibold">Estado pedido:</span>
                <span class="badge {{ $estadoPedidoClass }}">{{ $pedidoModel->estadoPedidoLabel() }}</span>
            </p>
            <p>
                <span class="font-semibold">Estado pago:</span>
                <span class="badge {{ $estadoPagoClass }}">{{ $pedidoModel->estadoPagoLabel() }}</span>
            </p>
        </div>

        <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4 text-sm">
            @if($metodo === 'transferencia')
                <p class="text-slate-700">Recibimos tu comprobante. Nuestro equipo validara la transferencia antes de aprobar el pago.</p>
            @elseif(in_array($metodo, ['paypal','payphone'], true) && !$providerConfirmed)
                <p class="text-slate-700">Estamos esperando confirmacion final del proveedor de pago.</p>
            @elseif(in_array($metodo, ['paypal','payphone'], true) && $providerConfirmed && $estadoPagoProveedor === 'APROBADO')
                <p class="text-emerald-700">Pago confirmado por el proveedor. Tu pedido pasara a preparacion.</p>
            @elseif($estadoPagoProveedor === 'RECHAZADO')
                <p class="text-rose-700">El pago fue rechazado. Puedes reintentar desde tu carrito.</p>
            @else
                <p class="text-slate-700">Te notificaremos cuando cambie el estado del pedido o del pago.</p>
            @endif
        </div>

        <div class="mt-6 flex flex-wrap justify-center gap-2">
            <a href="{{ route('ecommerce.productos.index') }}" class="btn btn-primary">Seguir comprando</a>
            <a href="{{ route('ecommerce.home') }}" class="btn btn-secondary">Volver al inicio</a>
        </div>
    </div>
@endsection

