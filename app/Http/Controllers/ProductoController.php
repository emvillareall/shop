<?php

namespace App\Http\Controllers;

use App\Models\CategoriasProducto;
use App\Models\Colores;
use App\Models\ColoresProducto;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\LineasRopa;
use App\Models\Parametro;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Services\Inventario\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProductoController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventarioService
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'linea' => trim((string) $request->query('linea', '')),
            'categoria' => trim((string) $request->query('categoria', '')),
            'estado_stock' => trim((string) $request->query('estado_stock', '')),
            'precio_min' => trim((string) $request->query('precio_min', '')),
            'precio_max' => trim((string) $request->query('precio_max', '')),
        ];

        $precioMin = str_replace(',', '.', $filters['precio_min']);
        $precioMax = str_replace(',', '.', $filters['precio_max']);

        if ($precioMin !== '' && $precioMax !== '' && is_numeric($precioMin) && is_numeric($precioMax) && (float) $precioMin > (float) $precioMax) {
            [$precioMin, $precioMax] = [$precioMax, $precioMin];
        }

        $query = Producto::query()
            ->with(['coloresStock.color', 'categoria.linea'])
            ->withSum('coloresStock as stock_variante_total', 'stock_por_color')
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('codigo_producto', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('descripcion_producto', 'like', '%' . $filters['search'] . '%');
                });
            })
            ->when($filters['linea'] !== '', function ($q) use ($filters) {
                $lineaId = (int) $filters['linea'];
                $q->whereHas('categoria', fn ($rel) => $rel->where('linea_ropa_id', $lineaId));
            })
            ->when($filters['categoria'] !== '', function ($q) use ($filters) {
                $q->where('categoria_producto_id', (int) $filters['categoria']);
            })
            ->when($precioMin !== '' && is_numeric($precioMin), function ($q) use ($precioMin) {
                $q->where('precio_venta_producto', '>=', (float) $precioMin);
            })
            ->when($precioMax !== '' && is_numeric($precioMax), function ($q) use ($precioMax) {
                $q->where('precio_venta_producto', '<=', (float) $precioMax);
            })
            ->when($filters['estado_stock'] !== '', function ($q) use ($filters) {
                $stockExpr = '(SELECT COALESCE(SUM(cp.stock_por_color),0) FROM colores_productos cp WHERE cp.producto_id = productos.id)';
                if ($filters['estado_stock'] === 'agotado') {
                    $q->whereRaw("$stockExpr <= 0");
                } elseif ($filters['estado_stock'] === 'poco') {
                    $q->whereRaw("$stockExpr > 0 AND $stockExpr <= 5");
                } elseif ($filters['estado_stock'] === 'disponible') {
                    $q->whereRaw("$stockExpr > 5");
                }
            })
            ->orderByDesc('id');

        $productos = $query->paginate(15)->appends($request->query());

        $lineas = LineasRopa::query()->orderBy('nombre_linea')->get(['id', 'nombre_linea']);
        $categorias = CategoriasProducto::query()
            ->when($filters['linea'] !== '', fn ($q) => $q->where('linea_ropa_id', (int) $filters['linea']))
            ->orderBy('nombre_categoria')
            ->get(['id', 'nombre_categoria', 'linea_ropa_id']);

        return view('producto.index', compact('productos', 'lineas', 'categorias', 'filters'));
    }

    public function create(Request $request)
    {
        $compras_id = $request->id;
        $producto = new Producto();
        $lineasRopa = LineasRopa::all();
        $categorias = CategoriasProducto::all();

        return view('producto.create', compact('producto', 'compras_id', 'lineasRopa', 'categorias'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_producto' => 'required',
            'descripcion_producto' => 'required',
            'cantidad_compra_producto' => 'required',
            'stock_venta_producto' => 'required',
            'precio_pesos_producto' => 'required',
            'precio_dolares_producto' => 'nullable',
            'precio_venta_producto' => 'nullable',
            'promocion_activa' => 'nullable|boolean',
            'precio_promocional' => 'nullable|numeric|min:0',
            'promocion_fecha_inicio' => 'nullable|date',
            'promocion_fecha_fin' => 'nullable|date|after_or_equal:promocion_fecha_inicio',
            'promocion_etiqueta' => 'nullable|string|max:60',
            'compras_id' => 'required',
            'categoria_producto_id' => 'required',
            'estado_producto' => 'nullable',
            'imagen_producto' => 'nullable',
            'imagen' => 'required|image|mimes:jpg,jpeg,png|max:4096',
            'imagenes.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'imagen_color_row' => 'nullable|array',
            'imagen_color_row.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $precioNormal = (float) ($validated['precio_venta_producto'] ?? 0);
        $promoActiva = (bool) ($request->boolean('promocion_activa'));
        $precioPromo = isset($validated['precio_promocional']) ? (float) $validated['precio_promocional'] : null;
        if ($promoActiva) {
            if ($precioPromo === null || $precioPromo <= 0) {
                return back()->withErrors(['precio_promocional' => 'El precio promocional es obligatorio cuando la promocion esta activa.'])->withInput();
            }
            if ($precioNormal > 0 && $precioPromo >= $precioNormal) {
                return back()->withErrors(['precio_promocional' => 'El precio promocional debe ser menor al precio de venta.'])->withInput();
            }
        }

        $validated['promocion_activa'] = $promoActiva ? 1 : 0;

        $producto = Producto::create(collect($validated)->except(['imagen', 'imagenes', 'imagen_color_row'])->all());

        if ($request->hasFile('imagen')) {
            $stored = $this->storeProductoImage($request->file('imagen'), (int) $producto->id);
            $producto->update(['imagen_producto' => $stored['ruta']]);
        }

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $idx => $fileGaleria) {
                $stored = $this->storeProductoImage($fileGaleria, (int) $producto->id);
                ProductoImagen::create([
                    'producto_id' => (int) $producto->id,
                    'ruta' => $stored['ruta'],
                    'nombre_original' => $stored['nombre_original'],
                    'mime_type' => $stored['mime_type'],
                    'peso_original' => $stored['peso_original'],
                    'peso_optimizado' => $stored['peso_optimizado'],
                    'ancho' => $stored['ancho'],
                    'alto' => $stored['alto'],
                    'orden' => $idx + 1,
                    'es_principal' => false,
                    'activo' => true,
                ]);
            }
        }

        $recargoPaypal = Parametro::where('nombre_parametro', 'recargo_paypal')->first();
        $cambioMoneda = Parametro::where('nombre_parametro', 'cambio_moneda')->first();

        $precioRealPesos = (float) $request->precio_pesos_producto + ((float) $request->precio_pesos_producto * (float) ($recargoPaypal?->valor_parametro ?? 0));

        if ((float) $request->precio_dolares_producto === 0.0) {
            $precioRealDolares = $precioRealPesos * (float) ($cambioMoneda?->valor_parametro ?? 1);
            DB::table('productos')
                ->where('codigo_producto', $request->codigo_producto)
                ->update(['precio_dolares_producto' => $precioRealDolares, 'precio_pesos_producto' => $precioRealPesos]);
        } else {
            DB::table('productos')
                ->where('codigo_producto', $request->codigo_producto)
                ->update(['precio_dolares_producto' => $request->precio_dolares_producto]);
            $precioRealDolares = (float) $request->precio_dolares_producto;
        }

        $compra = Compra::find($request->compras_id);
        $subtotalProductoPesos = $precioRealPesos * (float) $request->cantidad_compra_producto;
        $subtotalProductoDolares = $precioRealDolares * (float) $request->cantidad_compra_producto;

        if ((float) ($compra?->total_dolares_compra ?? 0) === 0.0) {
            $envioTemp = (float) ($compra?->importacion_compra ?? 0);
            $precioRealEnvio = (float) ($compra?->envio_compra ?? 0) + ((float) ($compra?->envio_compra ?? 0) * (float) ($recargoPaypal?->valor_parametro ?? 0));
            $precioEnvioDolares = $precioRealEnvio * (float) ($cambioMoneda?->valor_parametro ?? 1);
        } else {
            $envioTemp = 0;
            $precioRealEnvio = 0;
            $precioEnvioDolares = 0;
        }

        DB::table('compras')
            ->where('id', $request->compras_id)
            ->update([
                'total_pesos_compra' => (float) ($compra?->total_pesos_compra ?? 0) + $subtotalProductoPesos + $precioRealEnvio,
                'total_dolares_compra' => (float) ($compra?->total_dolares_compra ?? 0) + $subtotalProductoDolares + $precioEnvioDolares,
                'total_final_compra' => (float) ($compra?->total_final_compra ?? 0) + $subtotalProductoDolares + $precioEnvioDolares + $envioTemp,
            ]);

        $productoInsertado = DB::table('productos')->where('codigo_producto', $request->codigo_producto)->first();

        if ($request->has(['colores_id', 'talla_por_color', 'cantidad_por_color', 'stock_por_color'])) {
            $colores = $request->colores_id;
            $tallas = $request->talla_por_color;
            $cantidades = $request->cantidad_por_color;
            $stocks = $request->stock_por_color;

            DB::transaction(function () use ($productoInsertado, $colores, $tallas, $cantidades, $stocks, $request) {
                foreach ($colores as $i => $colorId) {
                    DB::table('colores_productos')->insert([
                        'producto_id' => $productoInsertado->id,
                        'colores_id' => $colorId,
                        'talla_por_color' => $tallas[$i] ?? '',
                        'cantidad_por_color' => (int) ($cantidades[$i] ?? 0),
                        'stock_por_color' => (int) ($stocks[$i] ?? 0),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $stockInicial = (int) ($stocks[$i] ?? 0);
                    $cantidadLinea = (int) ($cantidades[$i] ?? 0);
                    $ppu = (float) ($productoInsertado->precio_pesos_producto ?? 0);
                    $pdu = (float) ($productoInsertado->precio_dolares_producto ?? 0);

                    $compraItem = CompraItem::create([
                        'compra_id' => (int) $request->compras_id,
                        'producto_id' => (int) $productoInsertado->id,
                        'colores_id' => (int) $colorId,
                        'talla' => (string) ($tallas[$i] ?? ''),
                        'cantidad' => $cantidadLinea,
                        'stock_ingresado' => $stockInicial,
                        'costo_unitario_pesos' => $ppu,
                        'costo_unitario_dolares' => $pdu,
                        'subtotal_pesos' => $cantidadLinea * $ppu,
                        'subtotal_dolares' => $cantidadLinea * $pdu,
                        'estado' => 'ACTIVO',
                        'observacion' => 'Linea registrada desde alta de producto en compra',
                    ]);

                    if ($stockInicial > 0) {
                        $this->inventarioService->registrarEntradaVariante(
                            (int) $productoInsertado->id,
                            (int) $colorId,
                            (string) ($tallas[$i] ?? ''),
                            $stockInicial,
                            (int) $request->compras_id,
                            (int) $compraItem->id,
                            'Entrada inicial por carga de producto en compra'
                        );
                    }
                }
            });

            $this->syncColorImagesFromRows(
                (int) $producto->id,
                $request->input('colores_id', []),
                $this->extractColorRowFiles($request)
            );
        }

        $this->syncLegacyMainImageRecord($producto);

        return redirect()->route('compras.index');
    }

    public function show($id)
    {
        $producto = Producto::with(['imagenes', 'coloresStock.color'])->findOrFail($id);
        return view('producto.show', compact('producto'));
    }

    public function edit($id)
    {
        $producto = Producto::with(['coloresStock.color', 'imagenes'])->findOrFail($id);
        $compras_id = $producto->compras_id;
        $lineasRopa = LineasRopa::all();
        $categorias = CategoriasProducto::all();

        return view('producto.edit', compact('producto', 'compras_id', 'lineasRopa', 'categorias'));
    }

    public function update(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'codigo_producto' => 'required',
            'descripcion_producto' => 'required',
            'cantidad_compra_producto' => 'required',
            'stock_venta_producto' => 'required',
            'precio_pesos_producto' => 'required',
            'precio_dolares_producto' => 'nullable',
            'precio_venta_producto' => 'nullable',
            'promocion_activa' => 'nullable|boolean',
            'precio_promocional' => 'nullable|numeric|min:0',
            'promocion_fecha_inicio' => 'nullable|date',
            'promocion_fecha_fin' => 'nullable|date|after_or_equal:promocion_fecha_inicio',
            'promocion_etiqueta' => 'nullable|string|max:60',
            'compras_id' => 'required',
            'categoria_producto_id' => 'required',
            'estado_producto' => 'nullable',
            'imagen_producto' => 'nullable',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'imagenes.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'imagen_color_row' => 'nullable|array',
            'imagen_color_row.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'imagen_color_actual_id' => 'nullable|array',
            'imagen_color_actual_id.*' => 'nullable|integer',
            'desactivar_imagen_color_id' => 'nullable|array',
            'desactivar_imagen_color_id.*' => 'nullable|integer',
            'remove_imagenes' => 'array',
            'remove_imagenes.*' => 'integer',
            'variante_id' => 'array',
            'variante_id.*' => 'nullable|integer',
            'colores_id' => 'array',
            'colores_id.*' => 'nullable|integer',
            'talla_por_color' => 'array',
            'talla_por_color.*' => 'nullable|string|max:20',
            'cantidad_por_color' => 'array',
            'cantidad_por_color.*' => 'nullable|integer|min:0',
            'stock_por_color' => 'array',
            'stock_por_color.*' => 'nullable|integer|min:0',
        ]);

        $precioNormal = (float) ($validated['precio_venta_producto'] ?? 0);
        $promoActiva = (bool) ($request->boolean('promocion_activa'));
        $precioPromo = isset($validated['precio_promocional']) ? (float) $validated['precio_promocional'] : null;
        if ($promoActiva) {
            if ($precioPromo === null || $precioPromo <= 0) {
                return back()->withErrors(['precio_promocional' => 'El precio promocional es obligatorio cuando la promocion esta activa.'])->withInput();
            }
            if ($precioNormal > 0 && $precioPromo >= $precioNormal) {
                return back()->withErrors(['precio_promocional' => 'El precio promocional debe ser menor al precio de venta.'])->withInput();
            }
        }

        $validated['promocion_activa'] = $promoActiva ? 1 : 0;

        $data = collect($validated)->except([
            'imagen',
            'imagenes',
            'imagen_color_row',
            'imagen_color_actual_id',
            'desactivar_imagen_color_id',
            'remove_imagenes',
            'variante_id',
            'colores_id',
            'talla_por_color',
            'cantidad_por_color',
            'stock_por_color',
        ])->all();

        if ($request->hasFile('imagen')) {
            $oldMainImage = (string) $producto->imagen_producto;
            $storedMain = $this->storeProductoImage($request->file('imagen'), (int) $producto->id);
            $data['imagen_producto'] = $storedMain['ruta'];
            $this->deleteProductoImageFiles($oldMainImage);
        }

        DB::transaction(function () use ($request, $producto, $data) {
            $producto->update($data);

            $removeIds = collect($request->input('remove_imagenes', []))
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values();

            if ($removeIds->isNotEmpty()) {
                $imagenes = ProductoImagen::where('producto_id', (int) $producto->id)
                    ->whereIn('id', $removeIds)
                    ->get();

                foreach ($imagenes as $imagen) {
                    $this->deleteProductoImageFiles((string) $imagen->ruta);
                    $imagen->delete();
                }
            }

            if ($request->hasFile('imagenes')) {
                $ordenBase = (int) ($producto->imagenes()->max('orden') ?? 0);
                foreach ($request->file('imagenes') as $idx => $fileGaleria) {
                    $stored = $this->storeProductoImage($fileGaleria, (int) $producto->id);
                    ProductoImagen::create([
                        'producto_id' => (int) $producto->id,
                        'ruta' => $stored['ruta'],
                        'nombre_original' => $stored['nombre_original'],
                        'mime_type' => $stored['mime_type'],
                        'peso_original' => $stored['peso_original'],
                        'peso_optimizado' => $stored['peso_optimizado'],
                        'ancho' => $stored['ancho'],
                        'alto' => $stored['alto'],
                        'orden' => $ordenBase + $idx + 1,
                        'es_principal' => false,
                        'activo' => true,
                    ]);
                }
            }

            $desactivarImagenesColorIds = collect($request->input('desactivar_imagen_color_id', []))
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($desactivarImagenesColorIds->isNotEmpty()) {
                ProductoImagen::query()
                    ->where('producto_id', (int) $producto->id)
                    ->whereIn('id', $desactivarImagenesColorIds)
                    ->update([
                        'activo' => false,
                        'es_principal' => false,
                        'updated_at' => now(),
                    ]);
            }

            if (!$producto->imagenes()->where('activo', true)->where('es_principal', true)->exists()) {
                $firstImage = $producto->imagenes()->where('activo', true)->orderBy('orden')->orderBy('id')->first();
                if ($firstImage) {
                    $firstImage->update(['es_principal' => true]);
                }
            }

            $variantIds = $request->input('variante_id', []);
            $colorIds = $request->input('colores_id', []);
            $tallas = $request->input('talla_por_color', []);
            $cantidades = $request->input('cantidad_por_color', []);
            $stocks = $request->input('stock_por_color', []);

            $keepVariantIds = [];
            foreach ($colorIds as $idx => $colorId) {
                $colorId = (int) $colorId;
                $talla = trim((string) ($tallas[$idx] ?? ''));
                if ($colorId <= 0 || $talla === '') {
                    continue;
                }

                $payload = [
                    'colores_id' => $colorId,
                    'talla_por_color' => $talla,
                    'cantidad_por_color' => (int) ($cantidades[$idx] ?? 0),
                    'stock_por_color' => (int) ($stocks[$idx] ?? 0),
                ];

                $varianteId = (int) ($variantIds[$idx] ?? 0);
                if ($varianteId > 0) {
                    $variante = ColoresProducto::where('id', $varianteId)
                        ->where('producto_id', (int) $producto->id)
                        ->first();
                    if ($variante) {
                        $variante->update($payload);
                        $keepVariantIds[] = (int) $variante->id;
                        continue;
                    }
                }

                $created = ColoresProducto::create(array_merge($payload, [
                    'producto_id' => (int) $producto->id,
                ]));
                $keepVariantIds[] = (int) $created->id;
            }

            ColoresProducto::where('producto_id', (int) $producto->id)
                ->when(!empty($keepVariantIds), fn ($q) => $q->whereNotIn('id', $keepVariantIds))
                ->when(empty($keepVariantIds), fn ($q) => $q)
                ->delete();

            $this->syncColorImagesFromRows(
                (int) $producto->id,
                $request->input('colores_id', []),
                $this->extractColorRowFiles($request)
            );

            $this->syncLegacyMainImageRecord($producto->refresh());
        });

        return redirect()->route('productos.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy($id)
    {
        Producto::find($id)?->delete();
        return redirect()->route('productos.index')->with('success', 'Producto eliminado correctamente');
    }

    public function categoriasPorLinea(int $lineaId)
    {
        $categorias = CategoriasProducto::query()
            ->where('linea_ropa_id', $lineaId)
            ->where('estado_categoria', 1)
            ->orderBy('nombre_categoria')
            ->get(['id', 'nombre_categoria']);

        return response()->json($categorias);
    }

    private function storeProductoImage($file, ?int $productoId = null, ?string $colorSlug = null): array
    {
        $safeColor = $colorSlug ? Str::slug(Str::lower($colorSlug)) : null;
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::uuid() . '.webp';
        $directory = $productoId
            ? 'productos/' . $productoId . ($safeColor ? '/colores/' . $safeColor : '/general')
            : 'productos';

        $manager = new ImageManager(new Driver());
        $image = $manager->read($file);
        $image = $image->scale(width: min(1200, $image->width()));
        $encodedImage = $image->toWebp(80);
        $ruta = $directory . '/' . $filename;
        Storage::disk('public')->put($ruta, $encodedImage);

        return [
            'ruta' => $ruta,
            'nombre_original' => (string) $file->getClientOriginalName(),
            'mime_type' => 'image/webp',
            'peso_original' => (int) $file->getSize(),
            'peso_optimizado' => (int) strlen((string) $encodedImage),
            'ancho' => (int) $image->width(),
            'alto' => (int) $image->height(),
            'extension_origen' => $extension,
        ];
    }

    private function deleteProductoImageFiles(string $ruta): void
    {
        if ($ruta === '') {
            return;
        }

        $normalized = ltrim(str_replace('\\', '/', $ruta), '/');
        if (Storage::disk('public')->exists($normalized)) {
            Storage::disk('public')->delete($normalized);
        }

        $webp = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $normalized);
        if ($webp && Storage::disk('public')->exists($webp)) {
            Storage::disk('public')->delete($webp);
        }
    }

    private function syncColorImagesFromRows(int $productoId, array $colorIds, array $filesByRow): void
    {
        foreach ($colorIds as $idx => $rawColorId) {
            $colorId = (int) $rawColorId;
            $file = $filesByRow[$idx] ?? null;

            if ($colorId <= 0 || !$file) {
                continue;
            }

            $color = Colores::query()->find($colorId);
            $colorNombre = trim((string) ($color?->nombre_color ?? 'color-' . $colorId));
            $colorNormalizado = Str::slug(Str::lower($colorNombre));

            $stored = $this->storeProductoImage($file, $productoId, $colorNormalizado);

            $existing = ProductoImagen::query()
                ->where('producto_id', $productoId)
                ->where('color_id', $colorId)
                ->orderByDesc('es_principal')
                ->orderBy('id')
                ->first();

            if ($existing) {
                $this->deleteProductoImageFiles((string) $existing->ruta);
                $existing->update([
                    'ruta' => $stored['ruta'],
                    'nombre_original' => $stored['nombre_original'],
                    'mime_type' => $stored['mime_type'],
                    'peso_original' => $stored['peso_original'],
                    'peso_optimizado' => $stored['peso_optimizado'],
                    'ancho' => $stored['ancho'],
                    'alto' => $stored['alto'],
                    'color_nombre' => $colorNombre,
                    'color_normalizado' => $colorNormalizado,
                    'activo' => true,
                ]);
                continue;
            }

            ProductoImagen::create([
                'producto_id' => $productoId,
                'color_id' => $colorId,
                'color_nombre' => $colorNombre,
                'color_normalizado' => $colorNormalizado,
                'ruta' => $stored['ruta'],
                'nombre_original' => $stored['nombre_original'],
                'mime_type' => $stored['mime_type'],
                'peso_original' => $stored['peso_original'],
                'peso_optimizado' => $stored['peso_optimizado'],
                'ancho' => $stored['ancho'],
                'alto' => $stored['alto'],
                'orden' => (int) (ProductoImagen::where('producto_id', $productoId)->max('orden') ?? 0) + 1,
                'es_principal' => !ProductoImagen::where('producto_id', $productoId)->exists(),
                'activo' => true,
            ]);
        }
    }

    private function extractColorRowFiles(Request $request): array
    {
        $raw = $request->file('imagen_color_row', []);
        if ($raw instanceof \Illuminate\Http\UploadedFile) {
            return [0 => $raw];
        }

        if (is_array($raw)) {
            return collect($raw)
                ->filter(fn ($item) => $item instanceof \Illuminate\Http\UploadedFile)
                ->mapWithKeys(fn ($item, $key) => [(int) $key => $item])
                ->all();
        }

        return [];
    }

    private function syncLegacyMainImageRecord(Producto $producto): void
    {
        $legacy = trim((string) $producto->imagen_producto);
        if ($legacy === '') {
            return;
        }

        $normalized = ltrim(str_replace('\\', '/', $legacy), '/');
        $normalized = preg_replace('#^storage/#', '', $normalized);

        $alreadyExists = ProductoImagen::query()
            ->where('producto_id', (int) $producto->id)
            ->where('ruta', $normalized)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        $hasAnyImage = ProductoImagen::query()
            ->where('producto_id', (int) $producto->id)
            ->exists();

        ProductoImagen::create([
            'producto_id' => (int) $producto->id,
            'ruta' => $normalized,
            'orden' => (int) (ProductoImagen::where('producto_id', (int) $producto->id)->max('orden') ?? 0) + 1,
            'es_principal' => !$hasAnyImage,
            'activo' => true,
            'nombre_original' => basename($normalized),
        ]);
    }
}
