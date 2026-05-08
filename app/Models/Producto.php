<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Class Producto
 *
 * @property $id
 * @property $codigo_producto
 * @property $descripcion_producto
 * @property $cantidad_compra_producto
 * @property $stock_venta_producto
 * @property $precio_pesos_producto
 * @property $precio_dolares_producto
 * @property $precio_venta_producto
 * @property $estado_producto
 * @property $compras_id
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Producto extends Model
{
    
    static $rules = [
		'codigo_producto' => 'required',
		'descripcion_producto' => 'required',
		'cantidad_compra_producto' => 'required',
		'stock_venta_producto' => 'required',
		'precio_pesos_producto' => 'required',
		'precio_dolares_producto',
		'precio_venta_producto',
		'compras_id' => 'required',
		'categoria_producto_id' => 'required',
		'estado_producto',
		'imagen_producto'
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['codigo_producto','descripcion_producto','cantidad_compra_producto','stock_venta_producto','precio_pesos_producto','precio_dolares_producto','precio_venta_producto','estado_producto','imagen_producto','compras_id','categoria_producto_id'];

public function coloresStock()
{
    return $this->hasMany(ColoresProducto::class, 'producto_id')->with('color');
}

public function categoria()
{
    return $this->belongsTo(CategoriasProducto::class, 'categoria_producto_id');
}

public function imageUrl(): string
{
    $raw = trim((string) $this->imagen_producto);
    if ($raw !== '') {
        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return $raw;
        }

        $normalized = str_replace('\\', '/', ltrim($raw, '/'));

        if (Storage::disk('public')->exists($normalized)) {
            return route('media.producto', ['filename' => basename($normalized)]);
        }

        $publicPath = public_path($normalized);
        if (File::exists($publicPath)) {
            return route('media.producto', ['filename' => basename($normalized)]);
        }

        if (str_starts_with($normalized, 'storage/') && File::exists(public_path($normalized))) {
            return route('media.producto', ['filename' => basename($normalized)]);
        }

        // Fallbacks comunes legacy
        $basename = basename($normalized);
        $candidates = [
            'storage/productos/' . $basename,
            'imagenes/' . $basename,
            'imagenes/Landingpage/' . $basename,
        ];
        foreach ($candidates as $candidate) {
            if (File::exists(public_path($candidate))) {
                return route('media.producto', ['filename' => basename($candidate)]);
            }
        }
    }

    return 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=900&q=80';
}

public function imageWebpUrl(): ?string
{
    if (!$this->imagen_producto) {
        return null;
    }

    $path = ltrim($this->imagen_producto, '/');
    $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $path);
    if (!$webpPath || $webpPath === $path) {
        return null;
    }

    if (Storage::disk('public')->exists($webpPath)) {
        return route('media.producto', ['filename' => basename($webpPath)]);
    }

    return null;
}

public function stockTotalVariante(): int
{
    if (isset($this->stock_total_variante)) {
        return (int) $this->stock_total_variante;
    }

    if ($this->relationLoaded('coloresStock')) {
        return (int) $this->coloresStock->sum(fn ($item) => (int) $item->stock_por_color);
    }

    return (int) $this->coloresStock()->sum('stock_por_color');
}

public function variantesConStock(): int
{
    if (isset($this->variantes_con_stock)) {
        return (int) $this->variantes_con_stock;
    }

    if ($this->relationLoaded('coloresStock')) {
        return (int) $this->coloresStock->filter(fn ($item) => (int) $item->stock_por_color > 0)->count();
    }

    return (int) $this->coloresStock()->where('stock_por_color', '>', 0)->count();
}

public function disponibilidadTexto(): string
{
    $stock = $this->stockTotalVariante();
    $variantes = $this->variantesConStock();

    if ($stock <= 0) {
        return 'Agotado';
    }

    if ($stock <= 5) {
        return 'Pocas unidades';
    }

    return $variantes > 1 ? 'Disponible en tallas' : 'Disponible';
}

public function disponibilidadBadgeClass(): string
{
    $stock = $this->stockTotalVariante();

    if ($stock <= 0) {
        return 'bg-rose-100 text-rose-700';
    }

    if ($stock <= 5) {
        return 'bg-amber-100 text-amber-700';
    }

    return 'bg-emerald-100 text-emerald-700';
}

public function disponibilidadTextClass(): string
{
    $stock = $this->stockTotalVariante();

    if ($stock <= 0) {
        return 'text-rose-600';
    }

    if ($stock <= 5) {
        return 'text-amber-600';
    }

    return 'text-emerald-600';
}

}
