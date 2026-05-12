<?php

namespace App\Services\Ventas;

use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Venta;
use App\Models\VentaAbono;
use Illuminate\Support\Facades\DB;

class AbonoService
{
    public function registrarAbono(
        Venta $venta,
        float $monto,
        string $metodo,
        ?string $referencia = null,
        ?string $comprobantePath = null,
        ?string $observacion = null,
        ?int $registradoPor = null
    ): VentaAbono {
        return DB::transaction(function () use ($venta, $monto, $metodo, $referencia, $comprobantePath, $observacion, $registradoPor) {
            $venta = Venta::query()->lockForUpdate()->findOrFail($venta->id);
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($venta->pedido_id);

            $totalVenta = (float) ($venta->total ?? 0);
            $abonadoActual = (float) VentaAbono::query()->where('venta_id', $venta->id)->sum('monto');
            $saldoActual = max(0, $totalVenta - $abonadoActual);

            $monto = round($monto, 2);
            if ($monto <= 0) {
                throw new \RuntimeException('El abono debe ser mayor a cero.');
            }
            if ($monto > $saldoActual) {
                throw new \RuntimeException('El abono supera el saldo pendiente.');
            }

            $abono = VentaAbono::query()->create([
                'venta_id' => $venta->id,
                'pedido_id' => $venta->pedido_id,
                'monto' => $monto,
                'metodo' => $metodo,
                'referencia' => $referencia,
                'comprobante_path' => $comprobantePath,
                'observacion' => $observacion,
                'fecha_abono' => now(),
                'registrado_por' => $registradoPor,
            ]);

            $abonadoNuevo = $abonadoActual + $monto;
            $saldoNuevo = max(0, round($totalVenta - $abonadoNuevo, 2));
            $completo = $saldoNuevo <= 0.00001;

            $nuevoEstadoPago = $completo ? 'APROBADO' : 'PENDIENTE';
            $nuevoEstadoPedido = $completo ? 'PAGADO' : 'PENDIENTE_PAGO';
            $nuevoEstadoVenta = $completo ? 'PAGADO' : 'PENDIENTE_ABONO';

            $pedido->update([
                'estado_pago' => $nuevoEstadoPago,
                'estado_pedido' => $nuevoEstadoPedido,
                'pagado_at' => $completo ? now() : null,
            ]);

            $venta->update([
                'estado_venta' => $nuevoEstadoVenta,
                'fecha_ultimo_abono' => now(),
                'fecha_proximo_abono' => $completo ? null : now()->addDays(7),
            ]);

            Pago::query()->updateOrCreate(
                ['pedido_id' => $pedido->id, 'metodo' => 'apartado'],
                [
                    'estado' => $nuevoEstadoPago,
                    'monto' => $totalVenta,
                    'moneda' => 'USD',
                    'referencia_externa' => $referencia,
                    'observacion' => $completo
                        ? 'Pago completado por abonos.'
                        : 'Pago parcial por abonos. Saldo pendiente: ' . number_format($saldoNuevo, 2),
                    'metadata' => [
                        'origen' => 'apartado',
                        'abonado' => round($abonadoNuevo, 2),
                        'saldo' => round($saldoNuevo, 2),
                    ],
                    'revisado_por' => $registradoPor,
                    'revisado_at' => now(),
                    'aprobado_at' => $completo ? now() : null,
                    'rechazado_at' => null,
                ]
            );

            return $abono;
        });
    }
}
