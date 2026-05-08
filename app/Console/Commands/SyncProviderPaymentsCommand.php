<?php

namespace App\Console\Commands;

use App\Models\Pago;
use App\Services\Pagos\PaymentGatewayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncProviderPaymentsCommand extends Command
{
    protected $signature = 'payments:sync-provider
                            {--provider= : paypal|payphone}
                            {--limit=100 : Maximo de pagos a revisar}';

    protected $description = 'Sincroniza pagos pendientes/en revision con proveedor para conciliacion estricta.';

    public function handle(PaymentGatewayService $gateway): int
    {
        $provider = (string) $this->option('provider');
        $limit = max(1, (int) $this->option('limit'));

        $query = Pago::query()
            ->whereIn('metodo', ['paypal', 'payphone'])
            ->whereIn('estado', ['PENDIENTE', 'EN_REVISION'])
            ->whereNotNull('referencia_externa')
            ->orderBy('id');

        if ($provider) {
            $query->where('metodo', $provider);
        }

        $pagos = $query->limit($limit)->get();
        if ($pagos->isEmpty()) {
            $this->info('No hay pagos pendientes/en revision para sincronizar.');
            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($pagos as $pago) {
            $result = $pago->metodo === 'paypal'
                ? $gateway->syncPaypalPayment($pago)
                : $gateway->syncPayphonePayment($pago);

            $confirmed = (bool) data_get($result, 'confirmed', false);
            $status = strtoupper((string) data_get($result, 'status', 'UNKNOWN'));

            DB::transaction(function () use ($pago, $result, $confirmed, $status, &$updated) {
                $pago = Pago::query()->whereKey($pago->id)->lockForUpdate()->firstOrFail();
                $metadata = (array) ($pago->metadata ?? []);
                $metadata['provider_sync_last'] = now()->toIso8601String();
                $metadata['provider_sync_result'] = $result;
                $metadata['provider_confirmed'] = $confirmed;

                if ($confirmed) {
                    $pago->update([
                        'estado' => 'APROBADO',
                        'metadata' => $metadata,
                        'aprobado_at' => $pago->aprobado_at ?: now(),
                        'rechazado_at' => null,
                    ]);
                    $pago->pedido()->update([
                        'estado_pago' => 'APROBADO',
                        'estado_pedido' => 'PAGADO',
                        'pagado_at' => now(),
                    ]);
                    $updated++;
                    return;
                }

                if (in_array($status, ['DECLINED', 'DENIED', 'REJECTED', 'FAILED', 'VOIDED', 'CANCELLED'], true)) {
                    $pago->update([
                        'estado' => 'RECHAZADO',
                        'metadata' => $metadata,
                        'rechazado_at' => now(),
                        'aprobado_at' => null,
                    ]);
                    $pago->pedido()->update([
                        'estado_pago' => 'RECHAZADO',
                        'estado_pedido' => 'RECHAZADO',
                        'pagado_at' => null,
                    ]);
                    $updated++;
                    return;
                }

                $pago->update([
                    'estado' => 'EN_REVISION',
                    'metadata' => $metadata,
                ]);
            });
        }

        $this->info("Pagos sincronizados: {$pagos->count()} | Cambios de estado: {$updated}");
        return self::SUCCESS;
    }
}

