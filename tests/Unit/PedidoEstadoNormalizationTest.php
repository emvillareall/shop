<?php

namespace Tests\Unit;

use App\Models\Pedido;
use PHPUnit\Framework\TestCase;

class PedidoEstadoNormalizationTest extends TestCase
{
    public function test_fallbacks_are_normalized_from_legacy_fields(): void
    {
        $pedido = new Pedido([
            'estado_url' => 'ENVIADO',
            'estado_pedido' => null,
            'estado_pago' => null,
            'estado_envio' => null,
        ]);

        $this->assertSame('DESPACHADO', $pedido->estadoPedidoNormalizado());
        $this->assertSame('SIN_PAGO', $pedido->estadoPagoNormalizado());
        $this->assertSame('ENVIADO', $pedido->estadoEnvioNormalizado());
    }
}

