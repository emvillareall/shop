<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoImagen;
use Illuminate\Support\Facades\File;

class MediaController extends Controller
{
    public function producto(string $filename)
    {
        $filename = urldecode($filename);
        $filename = str_replace('\\', '/', $filename);
        $filename = ltrim($filename, '/');
        $filename = preg_replace('#/+#', '/', $filename);

        if (!$filename || str_contains($filename, '..') || str_starts_with($filename, '.')) {
            abort(404);
        }

        if (!auth()->check()) {
            $this->ensureLinkedProductIsVisible($filename);
        }

        $candidates = [
            storage_path('app/public/' . $filename),
            public_path($filename),
            public_path('storage/' . $filename),
            public_path('imagenes/' . $filename),
            public_path('imagenes/Landingpage/' . $filename),
        ];

        foreach ($candidates as $path) {
            if (File::exists($path)) {
                return response()->file($path);
            }
        }

        abort(404);
    }

    private function ensureLinkedProductIsVisible(string $filename): void
    {
        $paths = $this->equivalentPaths($filename);
        $productIds = ProductoImagen::query()
            ->whereIn('ruta', $paths)
            ->pluck('producto_id');

        $productIds = $productIds->merge(
            Producto::query()
                ->whereIn('imagen_producto', $paths)
                ->pluck('id')
        )->unique()->values();

        // Unlinked media remains available for landing/global assets. Product
        // media, however, must follow the same public visibility rule.
        if ($productIds->isNotEmpty() && !Producto::query()
            ->visibleEcommerce()
            ->whereKey($productIds->all())
            ->exists()) {
            abort(404);
        }
    }

    private function equivalentPaths(string $filename): array
    {
        $paths = [
            $filename,
            '/' . $filename,
            'storage/' . $filename,
            '/storage/' . $filename,
            'public/' . $filename,
            '/public/' . $filename,
            'media/productos/' . $filename,
            '/media/productos/' . $filename,
        ];

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($extension, ['webp', 'jpg', 'jpeg', 'png', 'gif'], true)) {
            $withoutExtension = substr($filename, 0, -strlen($extension) - 1);
            foreach (['webp', 'jpg', 'jpeg', 'png', 'gif'] as $candidateExtension) {
                $candidate = $withoutExtension . '.' . $candidateExtension;
                $paths[] = $candidate;
                $paths[] = 'storage/' . $candidate;
                $paths[] = 'public/' . $candidate;
                $paths[] = 'media/productos/' . $candidate;
            }
        }

        return array_values(array_unique($paths));
    }
}
