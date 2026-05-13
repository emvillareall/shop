@extends('layouts.ecommerce', ['title' => 'Pago PayPhone | Booty Fitness'])

@section('content')
    <section class="mx-auto max-w-3xl rounded-2xl border border-purple-100 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-900">Pago con PayPhone</h1>
        <p class="mt-1 text-sm text-slate-600">
            Pedido {{ $pago->pedido->codigo_pedido ?? ('#' . $pago->pedido_id) }} - Total ${{ number_format((float) $pago->monto, 2) }}
        </p>

        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div id="pp-button" class="min-h-16"></div>
            <p id="pp-debug" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"></p>
        </div>

        <p class="mt-4 text-xs text-slate-500">
            Seguridad: el pedido solo se marca como pagado despues de confirmar la transaccion en backend con PayPhone.
        </p>
    </section>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.payphonetodoesposible.com/box/v2.0/payphone-payment-box.css">
@endpush

@push('scripts')
    <script type="module" src="https://cdn.payphonetodoesposible.com/box/v2.0/payphone-payment-box.js"></script>
    <script>
        (function () {
            const data = @json($payload);
            const debugNode = document.getElementById('pp-debug');
            const showDebug = (msg) => {
                if (!debugNode) return;
                debugNode.textContent = msg;
                debugNode.classList.remove('hidden');
            };

            if (!data.storeId || !data.token || !data.responseUrl) {
                showDebug('Falta configuracion de PayPhone (storeId/token/responseUrl). Revisa Pasarelas.');
                return;
            }

            if (window.location.protocol !== 'https:' && window.location.hostname !== '127.0.0.1' && window.location.hostname !== 'localhost') {
                showDebug('PayPhone requiere HTTPS para funcionar correctamente en web.');
            }

            const renderBox = () => {
                console.info('PayPhone debug', {
                    storeId: data.storeId,
                    currency: data.currency,
                    amount: data.amount,
                    env: data.environment || 'no-definido',
                    responseUrl: data.responseUrl,
                });

                new PPaymentButtonBox({
                    token: data.token,
                    clientTransactionId: data.clientTransactionId,
                    amount: data.amount,
                    amountWithTax: data.amountWithTax,
                    amountWithoutTax: data.amountWithoutTax,
                    tax: data.tax,
                    service: data.service || 0,
                    tip: data.tip || 0,
                    currency: data.currency,
                    storeId: data.storeId,
                    reference: data.reference,
                    responseUrl: data.responseUrl,
                    lang: 'es',
                    displayAmount: true,
                    buttonColor: '#7c3aed',
                    buttonTextColor: '#ffffff',
                }).render('pp-button');
            };

            let attempts = 0;
            const maxAttempts = 30; // ~6s
            const timer = setInterval(() => {
                attempts++;
                if (window.PPaymentButtonBox) {
                    clearInterval(timer);
                    try {
                        renderBox();
                    } catch (e) {
                        console.error('PayPhone render error', e);
                        showDebug('PayPhone no pudo renderizar la cajita. Revisa dominio, storeId, modo (sandbox/produccion) y autorizacion en PayPhone Developer.');
                    }
                    return;
                }

                if (attempts >= maxAttempts) {
                    clearInterval(timer);
                    showDebug('No se pudo cargar el SDK de PayPhone. Verifica internet, dominio autorizado y consola del navegador.');
                }
            }, 200);
        })();
    </script>
@endpush
