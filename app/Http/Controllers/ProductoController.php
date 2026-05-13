<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Compra;
use App\Models\Parametro;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\LineasRopa; 
use App\Models\CategoriasProducto;
use App\Models\CompraItem;
use App\Services\Inventario\InventarioService;
use DB;

/**
 * Class ProductoController
 * @package App\Http\Controllers
 */
class ProductoController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventarioService
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
public function create(Request $request)
{
    $compras_id  = $request->id;
    $producto    = new Producto();
    $lineasRopa  = LineasRopa::all();            // <-- aquí
    $categorias  = CategoriasProducto::all();    // si usas todas o las dependientes

    return view('producto.create', compact('producto','compras_id','lineasRopa','categorias'));
}

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
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
            'compras_id' => 'required',
            'categoria_producto_id' => 'required',
            'estado_producto' => 'nullable',
            'imagen_producto' => 'nullable',
            'imagen' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $producto = Producto::create(collect($validated)->except('imagen')->all());
        

        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            $manager = new ImageManager(new Driver());
            $image = $manager->read($file);
            $image->scale(width: 800);

            // Codifica correctamente según la extensión:
            $extension = strtolower($file->getClientOriginalExtension());

            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $encodedImage = $image->toJpeg(75);
                    break;
                case 'png':
                    $encodedImage = $image->toPng();
                    break;
                case 'webp':
                    $encodedImage = $image->toWebp(75);
                    break;
                default:
                    $encodedImage = $image->toJpeg(75);
                    break;
            }

            // Guarda con Laravel Storage (correcto)
            Storage::disk('public')->put('productos/' . $filename, $encodedImage);
            $webpFilename = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $filename);
            if ($webpFilename) {
                Storage::disk('public')->put('productos/' . $webpFilename, $image->toWebp(75));
            }


$producto->update(['imagen_producto' => 'productos/' . $filename]);
        }

        $recargo_paypal= DB::table('parametros')->where('nombre_parametro','recargo_paypal')->first();
        $cambio_moneda= DB::table('parametros')->where('nombre_parametro','cambio_moneda')->first();


        $precio_real_pesos= $request->precio_pesos_producto + ($request->precio_pesos_producto * $recargo_paypal->valor_parametro);

        if($request->precio_dolares_producto == 0)
        {

        $precio_real_dolares=$precio_real_pesos * $cambio_moneda->valor_parametro ;
            DB::table('productos')
            ->where('codigo_producto', $request->codigo_producto)
            ->update(['precio_dolares_producto' => $precio_real_dolares,'precio_pesos_producto'=>$precio_real_pesos]);

        }
        else
        {
             DB::table('productos')
            ->where('codigo_producto', $request->codigo_producto)
            ->update(['precio_dolares_producto' => $request->precio_dolares_producto]);

            $precio_real_dolares=$request->precio_dolares_producto;
        }


        $compra = Compra::find($request->compras_id);

        $subtotal_producto_pesos= $precio_real_pesos * $request->cantidad_compra_producto;
        $subtotal_producto_dolares= $precio_real_dolares * $request->cantidad_compra_producto;

        
        if($compra->total_dolares_compra == '0')
        {
            $envio_temp=$compra->importacion_compra; 
            $precio_real_envio = $compra->envio_compra+($compra->envio_compra*$recargo_paypal->valor_parametro);
            $precio_envio_dolares = $precio_real_envio * $cambio_moneda->valor_parametro;
        }
        else
        {

            $envio_temp=0;
            $precio_real_envio = 0;
            $precio_envio_dolares = 0;
        }
 

        $total_pesos= $compra->total_pesos_compra + $subtotal_producto_pesos + $precio_real_envio;

        $total_dolares= $compra->total_dolares_compra + $subtotal_producto_dolares+$precio_envio_dolares;

        $total_final=$compra->total_final_compra+$subtotal_producto_dolares+$precio_envio_dolares+$envio_temp;

        //dd($total_final);

        DB::table('compras')
            ->where('id', $request->compras_id)
            ->update(['total_pesos_compra' => $total_pesos,'total_dolares_compra' => $total_dolares,'total_final_compra' => $total_final]);
        $producto= DB::table('productos')
            ->where('codigo_producto', $request->codigo_producto)->first();
        //dd($producto->id);

            //////////////////////////SEGUNDA OPCION PARA MODIFICAR //////////////////////////////////////////
// Guardar combinaciones color + talla + cantidad en colores_productos (si vienen en la solicitud)
if ($request->has(['colores_id', 'talla_por_color', 'cantidad_por_color', 'stock_por_color'])) {
    $colores = $request->colores_id;
    $tallas = $request->talla_por_color;
    $cantidades = $request->cantidad_por_color;
    $stocks = $request->stock_por_color;

    DB::transaction(function () use ($producto, $colores, $tallas, $cantidades, $stocks, $request) {
        foreach ($colores as $i => $color_id) {
            DB::table('colores_productos')->insert([
                'producto_id' => $producto->id,
                'colores_id' => $color_id,
                'talla_por_color' => $tallas[$i],
                'cantidad_por_color' => $cantidades[$i],
                'stock_por_color' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $stockInicial = (int) ($stocks[$i] ?? 0);
            $cantidadLinea = (int) ($cantidades[$i] ?? 0);
            $ppu = (float) ($producto->precio_pesos_producto ?? 0);
            $pdu = (float) ($producto->precio_dolares_producto ?? 0);

            $compraItem = CompraItem::create([
                'compra_id' => (int) $request->compras_id,
                'producto_id' => (int) $producto->id,
                'colores_id' => (int) $color_id,
                'talla' => (string) $tallas[$i],
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
                    (int) $producto->id,
                    (int) $color_id,
                    (string) $tallas[$i],
                    $stockInicial,
                    (int) $request->compras_id,
                    (int) $compraItem->id,
                    'Entrada inicial por carga de producto en compra'
                );
            }
        }
    });
}

return redirect()->route('compras.index');


   //     return redirect()->route('colores-productos.create',['id' => $producto->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $producto = Producto::find($id);

        return view('producto.show', compact('producto'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
public function edit($id)
{
    $producto    = Producto::findOrFail($id);
    $compras_id  = $producto->compras_id;
    $lineasRopa  = LineasRopa::all();            // <-- aquí
    $categorias  = CategoriasProducto::all();

    return view('producto.edit', compact('producto','compras_id','lineasRopa','categorias'));
}

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Producto $producto
     * @return \Illuminate\Http\Response
     */
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
        'compras_id' => 'required',
        'categoria_producto_id' => 'required',
        'estado_producto' => 'nullable',
        'imagen_producto' => 'nullable',
        'imagen' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
    ]);

    $data = collect($validated)->except('imagen')->all();

    if ($request->hasFile('imagen')) {
        $file = $request->file('imagen');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        $image = $manager->read($file);
        $image->scale(width: 800);

        $extension = strtolower($file->getClientOriginalExtension());

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $encodedImage = $image->toJpeg(75);
                break;
            case 'png':
                $encodedImage = $image->toPng();
                break;
            case 'webp':
                $encodedImage = $image->toWebp(75);
                break;
            default:
                $encodedImage = $image->toJpeg(75);
                break;
        }

        Storage::disk('public')->put('productos/' . $filename, $encodedImage);
        $webpFilename = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $filename);
        if ($webpFilename) {
            Storage::disk('public')->put('productos/' . $webpFilename, $image->toWebp(75));
        }

        // Actualiza la ruta de la imagen en los datos
        $data['imagen_producto'] = 'productos/' . $filename;
        // Mantiene el resto de datos validados ya cargados en $data
    } else {
        // Si no sube imagen, no modificas el campo 'imagen'
        unset($data['imagen']);
    }

    $producto->update($data);

    return redirect()->route('productos.index')
        ->with('success', 'Producto actualizado correctamente.');
}


    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $producto = Producto::find($id)->delete();

        return redirect()->route('productos.index')
            ->with('success', 'Producto deleted successfully');
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
}

