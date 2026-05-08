<?php

namespace App\Http\Controllers;

use App\Models\PaymentWebhookEvent;
use App\Services\Pagos\PaymentWebhookProcessor;
use App\Services\Pagos\WebhookSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookSignatureService $signatureService,
        private readonly PaymentWebhookProcessor $processor
    ) {}

    public function paypal(Request $request)
    {
        $rawBody = (string) $request->getContent();
        $payload = (array) ($request->json()->all() ?: []);
        $eventId = (string) data_get($payload, 'id', '');
        $eventType = (string) data_get($payload, 'event_type', 'UNKNOWN');
        $nonce = (string) $request->header('Paypal-Transmission-Id', '');
        $transmissionTime = (string) $request->header('Paypal-Transmission-Time', '');
        $payloadHash = hash('sha256', $rawBody);
        $validation = $this->signatureService->validatePaypal($request, $payload);
        $windowOk = $this->signatureService->isWithinReplayWindow($transmissionTime);

        if (!$windowOk) {
            return response()->json(['ok' => false, 'message' => 'Webhook outside replay window'], 400);
        }

        $replayByNonce = $nonce
            ? PaymentWebhookEvent::query()
                ->where('proveedor', 'paypal')
                ->where('nonce', $nonce)
                ->exists()
            : false;

        $replayByHash = PaymentWebhookEvent::query()
            ->where('proveedor', 'paypal')
            ->where('payload_hash', $payloadHash)
            ->where('created_at', '>=', now()->subHours(2))
            ->exists();

        if ($replayByNonce || $replayByHash) {
            return response()->json(['ok' => true, 'deduplicated' => true, 'replay_blocked' => true]);
        }

        if ($eventId && data_get($validation, 'valid', false)) {
            $alreadyProcessed = PaymentWebhookEvent::query()
                ->where('proveedor', 'paypal')
                ->where('evento_id', $eventId)
                ->where('firma_valida', true)
                ->where('procesado', true)
                ->exists();
            if ($alreadyProcessed) {
                return response()->json(['ok' => true, 'deduplicated' => true]);
            }
        }

        $transmitidoAt = null;
        if ($transmissionTime) {
            try {
                $transmitidoAt = Carbon::parse($transmissionTime);
            } catch (Throwable) {
                $transmitidoAt = null;
            }
        }

        $event = PaymentWebhookEvent::create([
            'proveedor' => 'paypal',
            'evento' => $eventType,
            'evento_id' => $eventId ?: null,
            'payload' => $payload,
            'payload_hash' => $payloadHash,
            'nonce' => $nonce ?: null,
            'transmitido_at' => $transmitidoAt,
            'firma_valida' => (bool) data_get($validation, 'valid', false),
            'procesado' => false,
            'error' => data_get($validation, 'reason'),
        ]);

        if (!$event->firma_valida) {
            return response()->json(['ok' => false, 'message' => 'Invalid signature'], 400);
        }

        try {
            $pago = $this->processor->process($event);
            $event->forceFill([
                'procesado' => true,
                'procesado_at' => now(),
                'pago_id' => $pago?->id,
                'intentos' => ((int) $event->intentos) + 1,
                'error' => null,
            ])->save();
        } catch (Throwable $e) {
            $event->forceFill([
                'procesado' => false,
                'intentos' => ((int) $event->intentos) + 1,
                'error' => $e->getMessage(),
            ])->save();
            throw $e;
        }

        return response()->json(['ok' => true]);
    }

    public function payphone(Request $request)
    {
        $rawBody = (string) $request->getContent();
        $payload = (array) ($request->json()->all() ?: []);
        $eventId = (string) data_get($payload, 'transactionId', data_get($payload, 'id', ''));
        $eventType = (string) data_get($payload, 'transactionStatus', data_get($payload, 'status', 'UNKNOWN'));
        $nonce = (string) ($request->header('X-Payphone-Event-Id') ?: $request->header('X-Request-Id') ?: '');
        $payloadHash = hash('sha256', $rawBody);
        $validation = $this->signatureService->validatePayphone($request);

        $replayByNonce = $nonce
            ? PaymentWebhookEvent::query()
                ->where('proveedor', 'payphone')
                ->where('nonce', $nonce)
                ->exists()
            : false;
        $replayByHash = PaymentWebhookEvent::query()
            ->where('proveedor', 'payphone')
            ->where('payload_hash', $payloadHash)
            ->where('created_at', '>=', now()->subHours(2))
            ->exists();
        if ($replayByNonce || $replayByHash) {
            return response()->json(['ok' => true, 'deduplicated' => true, 'replay_blocked' => true]);
        }

        if ($eventId && data_get($validation, 'valid', false)) {
            $alreadyProcessed = PaymentWebhookEvent::query()
                ->where('proveedor', 'payphone')
                ->where('evento_id', $eventId)
                ->where('firma_valida', true)
                ->where('procesado', true)
                ->exists();
            if ($alreadyProcessed) {
                return response()->json(['ok' => true, 'deduplicated' => true]);
            }
        }

        $event = PaymentWebhookEvent::create([
            'proveedor' => 'payphone',
            'evento' => $eventType,
            'evento_id' => $eventId ?: null,
            'payload' => $payload,
            'payload_hash' => $payloadHash,
            'nonce' => $nonce ?: null,
            'firma_valida' => (bool) data_get($validation, 'valid', false),
            'procesado' => false,
            'error' => data_get($validation, 'reason'),
        ]);

        if (!$event->firma_valida) {
            return response()->json(['ok' => false, 'message' => 'Invalid signature'], 400);
        }

        try {
            $pago = $this->processor->process($event);
            $event->forceFill([
                'procesado' => true,
                'procesado_at' => now(),
                'pago_id' => $pago?->id,
                'intentos' => ((int) $event->intentos) + 1,
                'error' => null,
            ])->save();
        } catch (Throwable $e) {
            $event->forceFill([
                'procesado' => false,
                'intentos' => ((int) $event->intentos) + 1,
                'error' => $e->getMessage(),
            ])->save();
            throw $e;
        }

        return response()->json(['ok' => true]);
    }
}
