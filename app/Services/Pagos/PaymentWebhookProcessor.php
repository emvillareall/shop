<?php

namespace App\Services\Pagos;

use App\Models\Pago;
use App\Models\PaymentWebhookEvent;
use Illuminate\Support\Facades\DB;

class PaymentWebhookProcessor
{
    public function __construct(
        private readonly PaymentGatewayService $gatewayService
    ) {}

    public function process(PaymentWebhookEvent $event): ?Pago
    {
        $payload = (array) ($event->payload ?? []);

        if ($event->proveedor === 'paypal') {
            return $this->processPaypal($payload);
        }

        if ($event->proveedor === 'payphone') {
            return $this->processPayphone($payload);
        }

        return null;
    }

    private function processPaypal(array $payload): ?Pago
    {
        $eventType = (string) data_get($payload, 'event_type', 'UNKNOWN');
        $resource = data_get($payload, 'resource', []);
        $customId = data_get($resource, 'purchase_units.0.custom_id');
        $providerOrderId = data_get($resource, 'id');

        $pago = null;
        if ($customId) {
            $pago = Pago::query()
                ->where('metodo', 'paypal')
                ->where('pedido_id', (int) $customId)
                ->latest('id')
                ->first();
        }

        if (!$pago && $providerOrderId) {
            $pago = Pago::query()
                ->where('metodo', 'paypal')
                ->where('referencia_externa', $providerOrderId)
                ->latest('id')
                ->first();
        }

        if (!$pago) {
            return null;
        }

        DB::transaction(function () use ($pago, $eventType, $providerOrderId, $payload) {
            $pago = Pago::query()->whereKey($pago->id)->lockForUpdate()->firstOrFail();
            $metadata = (array) ($pago->metadata ?? []);
            $metadata['provider_confirmed'] = false;
            $metadata['paypal_last_event'] = $eventType;
            $metadata['paypal_payload'] = $payload;

            $confirmation = $this->gatewayService->confirmPaypalFromWebhook($payload);
            $metadata['paypal_confirmation'] = $confirmation;
            $metadata['provider_confirmed'] = (bool) data_get($confirmation, 'confirmed', false);
            $providerStatus = strtoupper((string) data_get($confirmation, 'status', 'UNKNOWN'));
            $providerReference = (string) data_get($confirmation, 'reference', '');

            if ($metadata['provider_confirmed'] === true) {
                $pago->update([
                    'estado' => 'APROBADO',
                    'referencia_externa' => $providerReference ?: ($providerOrderId ?: $pago->referencia_externa),
                    'metadata' => $metadata,
                    'aprobado_at' => now(),
                    'rechazado_at' => null,
                ]);
                $pago->pedido()->update([
                    'estado_pago' => 'APROBADO',
                    'estado_pedido' => 'PAGADO',
                    'pagado_at' => now(),
                ]);
            } elseif (in_array($providerStatus, ['DECLINED', 'DENIED', 'REJECTED', 'FAILED', 'VOIDED'], true)) {
                $pago->update([
                    'estado' => 'RECHAZADO',
                    'referencia_externa' => $providerReference ?: ($providerOrderId ?: $pago->referencia_externa),
                    'metadata' => $metadata,
                    'rechazado_at' => now(),
                    'aprobado_at' => null,
                ]);
                $pago->pedido()->update([
                    'estado_pago' => 'RECHAZADO',
                    'estado_pedido' => 'RECHAZADO',
                    'pagado_at' => null,
                ]);
            } else {
                $pago->update([
                    'estado' => 'EN_REVISION',
                    'referencia_externa' => $providerReference ?: ($providerOrderId ?: $pago->referencia_externa),
                    'metadata' => $metadata,
                ]);
            }
        });

        return $pago->fresh();
    }

    private function processPayphone(array $payload): ?Pago
    {
        $eventId = (string) data_get($payload, 'transactionId', data_get($payload, 'id', ''));
        $eventType = (string) data_get($payload, 'transactionStatus', data_get($payload, 'status', 'UNKNOWN'));
        $clientTransactionId = data_get($payload, 'clientTransactionId');

        $pago = null;
        if ($clientTransactionId) {
            $pago = Pago::query()->find((int) $clientTransactionId);
        }
        if (!$pago && $eventId) {
            $pago = Pago::query()
                ->where('metodo', 'payphone')
                ->where('referencia_externa', $eventId)
                ->latest('id')
                ->first();
        }

        if (!$pago) {
            return null;
        }

        DB::transaction(function () use ($pago, $eventType, $eventId, $payload) {
            $pago = Pago::query()->whereKey($pago->id)->lockForUpdate()->firstOrFail();
            $metadata = (array) ($pago->metadata ?? []);
            $metadata['provider_confirmed'] = false;
            $metadata['payphone_last_event'] = $eventType;
            $metadata['payphone_payload'] = $payload;

            $confirmation = $this->gatewayService->confirmPayphoneFromWebhook($payload);
            $metadata['payphone_confirmation'] = $confirmation;
            $metadata['provider_confirmed'] = (bool) data_get($confirmation, 'confirmed', false);
            $providerStatus = strtoupper((string) data_get($confirmation, 'status', 'UNKNOWN'));
            $providerReference = (string) data_get($confirmation, 'reference', '');

            if ($metadata['provider_confirmed'] === true) {
                $pago->update([
                    'estado' => 'APROBADO',
                    'referencia_externa' => $providerReference ?: ($eventId ?: $pago->referencia_externa),
                    'metadata' => $metadata,
                    'aprobado_at' => now(),
                    'rechazado_at' => null,
                ]);
                $pago->pedido()->update([
                    'estado_pago' => 'APROBADO',
                    'estado_pedido' => 'PAGADO',
                    'pagado_at' => now(),
                ]);
            } elseif (in_array($providerStatus, ['DECLINED', 'REJECTED', 'FAILED', 'VOIDED', 'CANCELLED'], true)) {
                $pago->update([
                    'estado' => 'RECHAZADO',
                    'referencia_externa' => $providerReference ?: ($eventId ?: $pago->referencia_externa),
                    'metadata' => $metadata,
                    'rechazado_at' => now(),
                    'aprobado_at' => null,
                ]);
                $pago->pedido()->update([
                    'estado_pago' => 'RECHAZADO',
                    'estado_pedido' => 'RECHAZADO',
                    'pagado_at' => null,
                ]);
            } else {
                $pago->update([
                    'estado' => 'EN_REVISION',
                    'referencia_externa' => $providerReference ?: ($eventId ?: $pago->referencia_externa),
                    'metadata' => $metadata,
                ]);
            }
        });

        return $pago->fresh();
    }
}
