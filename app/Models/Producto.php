<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
 * @property $precio_promocional
 * @property $promocion_activa
 * @property $promocion_fecha_inicio
 * @property $promocion_fecha_fin
 * @property $promocion_etiqueta
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
    private const IMAGE_PLACEHOLDER = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 900 700'><rect width='900' height='700' fill='%23f1f5f9'/><rect x='80' y='80' width='740' height='540' rx='24' fill='%23e2e8f0'/><circle cx='300' cy='310' r='70' fill='%23cbd5e1'/><path d='M190 520l145-165 105 95 105-125 165 195H190z' fill='%2394a3b8'/><text x='450' y='610' text-anchor='middle' font-family='Arial,sans-serif' font-size='34' fill='%2364758b'>Sin imagen disponible</text></svg>";
    private static array $mediaExistsCache = [];
    private static ?array $globalBasenameIndex = null;
    
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
    protected $fillable = ['codigo_producto','descripcion_producto','cantidad_compra_producto','stock_venta_producto','precio_pesos_producto','precio_dolares_producto','precio_venta_producto','precio_promocional','promocion_activa','promocion_fecha_inicio','promocion_fecha_fin','promocion_etiqueta','estado_producto','imagen_producto','compras_id','categoria_producto_id'];

    protected $casts = [
        'precio_venta_producto' => 'decimal:2',
        'precio_promocional' => 'decimal:2',
        'promocion_activa' => 'boolean',
        'promocion_fecha_inicio' => 'datetime',
        'promocion_fecha_fin' => 'datetime',
    ];

public function precioNormal(): float
{
    return round((float) ($this->precio_venta_producto ?? 0), 2);
}

public function tienePromocionActiva(?\DateTimeInterface $ahora = null): bool
{
    if (!(bool) $this->promocion_activa) {
        return false;
    }

    $precioNormal = $this->precioNormal();
    $precioPromo = round((float) ($this->precio_promocional ?? 0), 2);
    if ($precioPromo <= 0 || $precioPromo >= $precioNormal) {
        return false;
    }

    $ahora = $ahora ? \Carbon\Carbon::instance($ahora) : now();
    if ($this->promocion_fecha_inicio && $ahora->lt($this->promocion_fecha_inicio)) {
        return false;
    }
    if ($this->promocion_fecha_fin && $ahora->gt($this->promocion_fecha_fin)) {
        return false;
    }

    return true;
}

public function precioFinal(): float
{
    if ($this->tienePromocionActiva()) {
        return round((float) $this->precio_promocional, 2);
    }

    return $this->precioNormal();
}

public function precioAnterior(): ?float
{
    return $this->tienePromocionActiva() ? $this->precioNormal() : null;
}

public function porcentajeDescuento(): float
{
    if (!$this->tienePromocionActiva()) {
        return 0.0;
    }

    $normal = $this->precioNormal();
    if ($normal <= 0) {
        return 0.0;
    }

    return round((($normal - $this->precioFinal()) / $normal) * 100, 2);
}

public function coloresStock()
{
    return $this->hasMany(ColoresProducto::class, 'producto_id')->with('color');
}

public function categoria()
{
    return $this->belongsTo(CategoriasProducto::class, 'categoria_producto_id');
}

public function imagenes()
{
    return $this->hasMany(ProductoImagen::class, 'producto_id')->orderBy('orden');
}

public function imageUrl(?int $colorId = null): string
{
    $galleryImage = $this->preferredImage($colorId);
    if ($galleryImage) {
        return $this->toMediaUrl($galleryImage->ruta);
    }

    $raw = trim((string) $this->imagen_producto);
    if ($raw !== '') {
        $legacy = $this->resolveLegacyImage($raw);
        if ($legacy) {
            return $legacy;
        }
    }

    return self::IMAGE_PLACEHOLDER;
}

public function imageWebpUrl(?int $colorId = null): ?string
{
    $basePath = null;

    $galleryImage = $this->preferredImage($colorId);
    if ($galleryImage) {
        $basePath = $this->normalizeMediaPath((string) $galleryImage->ruta);
    } elseif ($this->imagen_producto) {
        $basePath = $this->normalizeMediaPath((string) $this->imagen_producto);
    }

    if (!$basePath) {
        return null;
    }

    $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $basePath);
    if (!$webpPath || $webpPath === $basePath) {
        return null;
    }

    if ($this->mediaExists($webpPath)) {
        return route('media.producto', ['filename' => $webpPath]);
    }

    return null;
}

public function imagesForColor(?int $colorId = null)
{
    $query = $this->imagenes()->where('activo', true);

    if ($colorId !== null && $colorId > 0) {
        $byColor = (clone $query)->where('color_id', $colorId)->orderByDesc('es_principal')->get();
        if ($byColor->isNotEmpty()) {
            return $byColor;
        }
    }

    $general = (clone $query)
        ->where(function ($q) {
            $q->whereNull('color_id')->orWhere('color_normalizado', '');
        })
        ->orderByDesc('es_principal')
        ->get();

    if ($general->isNotEmpty()) {
        return $general;
    }

    return $this->imagenes()->orderByDesc('es_principal')->get();
}

public function imageUrlsForColor(?int $colorId = null): array
{
    $urls = [];

    foreach ($this->imagesForColor($colorId) as $img) {
        $ruta = (string) ($img->ruta ?? '');
        if ($ruta === '' || !$this->mediaExists($ruta)) {
            continue;
        }
        $urls[] = $this->toMediaUrl($ruta);
    }

    if (!empty($urls)) {
        return array_values(array_unique($urls));
    }

    $single = $this->imageUrl($colorId);
    return $single ? [$single] : [];
}

public function allImageUrls(): array
{
    $urls = [];

    foreach ($this->imagenes()->where('activo', true)->orderByDesc('es_principal')->orderBy('orden')->get() as $img) {
        $ruta = (string) ($img->ruta ?? '');
        if ($ruta === '' || !$this->mediaExists($ruta)) {
            continue;
        }
        $urls[] = $this->toMediaUrl($ruta);
    }

    if (!empty($urls)) {
        return array_values(array_unique($urls));
    }

    $single = $this->imageUrl();
    return $single ? [$single] : [];
}

public function colorKeyFromId(?int $colorId): ?string
{
    if (!$colorId) {
        return null;
    }

    $variant = $this->relationLoaded('coloresStock')
        ? $this->coloresStock->firstWhere('colores_id', $colorId)
        : $this->coloresStock()->with('color')->where('colores_id', $colorId)->first();

    $name = trim((string) optional(optional($variant)->color)->nombre_color);
    if ($name === '') {
        return null;
    }

    return Str::slug(Str::lower($name));
}

private function preferredImage(?int $colorId = null): ?ProductoImagen
{
    $images = $this->imagesForColor($colorId);
    return $images->first(fn ($img) => $this->mediaExists((string) $img->ruta));
}

private function resolveLegacyImage(string $raw): ?string
{
    $normalized = $this->normalizeMediaPath($raw);
    if (!$normalized) {
        return null;
    }

    if ($this->mediaExists($normalized)) {
        return $this->toMediaUrl($normalized);
    }

    $withExtension = $this->findPathByBasename($normalized);
    if ($withExtension) {
        return $this->toMediaUrl($withExtension);
    }

    // Fallbacks comunes legacy por basename
    $basename = basename($normalized);
    $candidates = [
        'productos/' . $basename,
        'imagenes/' . $basename,
        'imagenes/Landingpage/' . $basename,
    ];

    foreach ($candidates as $candidate) {
        if ($this->mediaExists($candidate)) {
            return $this->toMediaUrl($candidate);
        }

        $candidateByBasename = $this->findPathByBasename($candidate);
        if ($candidateByBasename) {
            return $this->toMediaUrl($candidateByBasename);
        }
    }

    $globalByBasename = $this->findGlobalPathByBasename(pathinfo($normalized, PATHINFO_FILENAME));
    if ($globalByBasename) {
        return $this->toMediaUrl($globalByBasename);
    }

    return null;
}

private function findPathByBasename(string $path): ?string
{
    $normalized = $this->normalizeMediaPath($path);
    if (!$normalized) {
        return null;
    }

    $dirname = trim((string) dirname($normalized), '.');
    $basename = pathinfo($normalized, PATHINFO_FILENAME);

    if ($basename === '') {
        return null;
    }

    $extensions = ['webp', 'jpg', 'jpeg', 'png', 'gif'];
    foreach ($extensions as $ext) {
        $candidate = ($dirname !== '' ? $dirname . '/' : '') . $basename . '.' . $ext;
        if ($this->mediaExists($candidate)) {
            return $candidate;
        }
    }

    return null;
}

private function mediaExists(string $path): bool
{
    $path = $this->normalizeMediaPath($path);
    if (!$path) {
        return false;
    }

    if (array_key_exists($path, self::$mediaExistsCache)) {
        return self::$mediaExistsCache[$path];
    }

    $exists = Storage::disk('public')->exists($path) || File::exists(public_path($path));
    self::$mediaExistsCache[$path] = $exists;

    return $exists;
}

private function toMediaUrl(string $path): string
{
    $path = $this->normalizeMediaPath($path) ?? '';
    return route('media.producto', ['filename' => $path]);
}

private function normalizeMediaPath(string $path): ?string
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        $urlPath = parse_url($path, PHP_URL_PATH);
        $path = is_string($urlPath) ? $urlPath : '';
    }

    $path = str_replace('\\', '/', trim($path));
    $path = ltrim($path, '/');
    $path = preg_replace('#^storage/#', '', $path);
    $path = preg_replace('#^public/#', '', $path);
    $path = preg_replace('#^media/productos/#', '', $path);
    $path = preg_replace('#/+#', '/', $path);

    if ($path === '' || str_contains($path, '..')) {
        return null;
    }

    return $path;
}

private function findGlobalPathByBasename(string $basename): ?string
{
    $basename = trim($basename);
    if ($basename === '') {
        return null;
    }

    if (self::$globalBasenameIndex === null) {
        self::$globalBasenameIndex = $this->buildGlobalBasenameIndex();
    }

    $key = Str::lower($basename);
    $first = self::$globalBasenameIndex[$key] ?? null;
    if (!$first) {
        return null;
    }

    $storagePublic = str_replace('\\', '/', storage_path('app/public/'));
    $publicBase = str_replace('\\', '/', public_path() . DIRECTORY_SEPARATOR);

    if (str_starts_with($first, $storagePublic)) {
        return ltrim(str_replace($storagePublic, '', $first), '/');
    }

    if (str_starts_with($first, $publicBase)) {
        return ltrim(str_replace($publicBase, '', $first), '/');
    }

    return null;
}

private function buildGlobalBasenameIndex(): array
{
    $index = [];
    $patterns = [
        storage_path('app/public/productos/*'),
        storage_path('app/public/*'),
        public_path('imagenes/*'),
        public_path('imagenes/Landingpage/*'),
        public_path('storage/productos/*'),
    ];

    foreach ($patterns as $pattern) {
        $matches = glob($pattern) ?: [];
        foreach ($matches as $match) {
            if (is_dir($match)) {
                $inner = glob(str_replace('\\', '/', $match) . '/*') ?: [];
                foreach ($inner as $file) {
                    if (!File::isFile($file)) {
                        continue;
                    }
                    $nameKey = Str::lower(pathinfo($file, PATHINFO_FILENAME));
                    $index[$nameKey] ??= str_replace('\\', '/', $file);
                }
                continue;
            }

            if (!File::isFile($match)) {
                continue;
            }

            $nameKey = Str::lower(pathinfo($match, PATHINFO_FILENAME));
            $index[$nameKey] ??= str_replace('\\', '/', $match);
        }
    }

    return $index;
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
