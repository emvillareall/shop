<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPaymentWebhookEventJob;
use App\Models\PaymentWebhookEvent;
use Illuminate\Console\Command;

class ReconcilePaymentWebhooksCommand extends Command
{
    protected $signature = 'payments:reconcile-webhooks
                            {--provider= : paypal|payphone}
                            {--limit=100 : Maximo de eventos}
                            {--queue : Encolar en lugar de procesar inline}';

    protected $description = 'Reprocesa eventos webhook validos pendientes o con error para conciliacion.';

    public function handle(): int
    {
        $provider = $this->option('provider');
        $limit = max(1, (int) $this->option('limit'));
        $maxRetries = (int) config('payments.webhooks.max_retries', 5);
        $useQueue = (bool) $this->option('queue');

        $query = PaymentWebhookEvent::query()
            ->where('firma_valida', true)
            ->where(function ($q) {
                $q->where('procesado', false)
                    ->orWhereNotNull('error');
            })
            ->where('intentos', '<', $maxRetries)
            ->orderBy('id');

        if ($provider) {
            $query->where('proveedor', $provider);
        }

        $events = $query->limit($limit)->get();
        if ($events->isEmpty()) {
            $this->info('No hay eventos pendientes para conciliar.');
            return self::SUCCESS;
        }

        foreach ($events as $event) {
            if ($useQueue) {
                ProcessPaymentWebhookEventJob::dispatch($event->id);
            } else {
                ProcessPaymentWebhookEventJob::dispatchSync($event->id);
            }
        }

        $this->info("Eventos enviados/procesados: {$events->count()}");

        return self::SUCCESS;
    }
}

