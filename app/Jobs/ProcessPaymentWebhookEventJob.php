<?php

namespace App\Jobs;

use App\Models\PaymentWebhookEvent;
use App\Services\Pagos\PaymentWebhookProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessPaymentWebhookEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $eventId) {}

    public function handle(PaymentWebhookProcessor $processor): void
    {
        $event = PaymentWebhookEvent::query()->find($this->eventId);
        if (!$event || !$event->firma_valida || $event->procesado) {
            return;
        }

        try {
            $pago = $processor->process($event);
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
    }
}

