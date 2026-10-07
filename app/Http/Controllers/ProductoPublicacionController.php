<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductoPublicacionController extends Controller
{
    public function publicar(Producto $producto): RedirectResponse
    {
        return $this->actualizarPublicacion($producto, true);
    }

    public function ocultar(Producto $producto): RedirectResponse
    {
        return $this->actualizarPublicacion($producto, false);
    }

    private function actualizarPublicacion(Producto $producto, bool $publicar): RedirectResponse
    {
        $this->authorize('update', $producto);

        DB::transaction(function () use ($producto, $publicar): void {
            $productoBloqueado = Producto::query()
                ->lockForUpdate()
                ->findOrFail($producto->getKey());

            if ($publicar && (int) $productoBloqueado->estado_producto !== 1) {
                throw ValidationException::withMessages([
                    'publicado_ecommerce' => 'El producto debe estar activo antes de publicarlo.',
                ]);
            }

            if ((bool) $productoBloqueado->publicado_ecommerce === $publicar) {
                return;
            }

            $productoBloqueado->publicado_ecommerce = $publicar;
            $productoBloqueado->saveQuietly();
        });

        return back()->with(
            'success',
            $publicar
                ? 'Producto publicado en ecommerce correctamente.'
                : 'Producto ocultado del ecommerce correctamente.'
        );
    }
}
