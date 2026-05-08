<?php

namespace Tests\Unit;

use App\Models\ColoresProducto;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class ProductoDisponibilidadTest extends TestCase
{
    public function test_disponibilidad_text_and_class_use_variant_stock(): void
    {
        $producto = new Producto();
        $producto->setRelation('coloresStock', new Collection([
            new ColoresProducto(['stock_por_color' => '3']),
            new ColoresProducto(['stock_por_color' => '1']),
        ]));

        $this->assertSame(4, $producto->stockTotalVariante());
        $this->assertSame('Pocas unidades', $producto->disponibilidadTexto());
        $this->assertSame('bg-amber-100 text-amber-700', $producto->disponibilidadBadgeClass());
        $this->assertSame('text-amber-600', $producto->disponibilidadTextClass());
    }
}

