<?php

namespace App\Http\Controllers;

use App\Models\CategoriasProducto;
use App\Models\Colores;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Pedido;
use App\Services\Inventario\InventarioService;
use App\Services\Inventario\StockReservaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EcommerceController extends Controller
{
    public function eliminarItemCarrito(int $index, StockReservaService $stockReservaService): RedirectResponse
    {
        $carrito = session()->get('carrito', []);
        if (isset($carrito[$index])) {
            unset($carrito[$index]);
            session()->put('carrito', array_values($carrito));
            $stockReservaService->syncFromCart(session()->getId(), session()->get('carrito', []));
            return redirect()->route('ecommerce.carrito.index')->with('success', 'Producto eliminado del carrito.');
        }

        return redirect()->route('ecommerce.carrito.index')->with('error', 'No se pudo eliminar el producto.');
    }

    public function vaciarCarrito(StockReservaService $stockReservaService): RedirectResponse
    {
        session()->forget('carrito');
        $stockReservaService->releaseSession(session()->getId());
        return redirect()->route('ecommerce.carrito.index')->with('success', 'Carrito vaciado correctamente.');
    }

    public function agregarCarrito(Request $request, InventarioService $inventarioService, StockReservaService $stockReservaService): RedirectResponse
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'color_id' => 'required|exists:colores,id',
            'talla' => 'required|string',
            'cantidad' => 'required|integer|min:1',
        ]);

        $stockDisponible = $inventarioService->stockDisponibleVariante(
            (int) $validated['producto_id'],
            (int) $validated['color_id'],
            (string) $validated['talla']
        );

        if ($stockDisponible < 1) {
            return back()->with('error', 'La variante seleccionada está agotada.');
        }

        $carrito = session()->get('carrito', []);
        $cantidadEnCarrito = collect($carrito)->filter(function ($item) use ($validated) {
            return (int) ($item['producto_id'] ?? 0) === (int) $validated['producto_id']
                && (int) ($item['color_id'] ?? 0) === (int) $validated['color_id']
                && (string) ($item['talla'] ?? '') === (string) $validated['talla'];
        })->sum(fn ($item) => (int) ($item['cantidad'] ?? 0));

        if (($cantidadEnCarrito + (int) $validated['cantidad']) > $stockDisponible) {
            return back()->with('error', 'No hay suficiente stock para esa combinación (color/talla).');
        }

        $producto = Producto::query()->findOrFail($validated['producto_id']);
        $color = Colores::query()->findOrFail($validated['color_id']);

        $carrito[] = [
            'producto_id' => $producto->id,
            'descripcion' => $producto->descripcion_producto,
            'color' => $color->nombre_color,
            'color_id' => $color->id,
            'talla' => $validated['talla'],
            'cantidad' => (int) $validated['cantidad'],
            'precio' => (float) $producto->precioFinal(),
            'precio_normal' => (float) $producto->precioNormal(),
            'precio_promocional_aplicado' => $producto->tienePromocionActiva() ? (float) $producto->precioFinal() : null,
        ];

        session()->put('carrito', $carrito);
        $stockReservaService->syncFromCart(session()->getId(), $carrito);

        return back()->with('success', 'Producto agregado al carrito.');
    }

    public function home(Request $request): View
    {
        $validated = $request->validate([
            'linea' => ['nullable', 'integer', Rule::exists('lineas_ropa', 'id')],
            'q' => ['nullable', 'string', 'max:120'],
            'categoria' => ['nullable', 'integer', Rule::exists('categorias_productos', 'id')],
        ]);

        $lineaSeleccionada = isset($validated['linea']) ? (int) $validated['linea'] : null;
        $searchTerm = trim((string) ($validated['q'] ?? ''));
        $categoriaSeleccionada = isset($validated['categoria']) ? (int) $validated['categoria'] : null;

        return view('ecommerce.home', compact(
            'lineaSeleccionada',
            'searchTerm',
            'categoriaSeleccionada',
        ));
    }

    public function catalogo(Request $request): View
    {
        $validated = $request->validate([
            'linea' => ['nullable', 'integer', Rule::exists('lineas_ropa', 'id')],
            'categoria' => ['nullable', 'integer', Rule::exists('categorias_productos', 'id')],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $lineaId = isset($validated['linea']) ? (int) $validated['linea'] : null;
        $categoriaId = isset($validated['categoria']) ? (int) $validated['categoria'] : null;
        $searchTerm = trim((string) ($validated['q'] ?? ''));

        return view('ecommerce.productos.index', compact('lineaId', 'categoriaId', 'searchTerm'));
    }

    public function categoria(CategoriasProducto $categoria): View
    {
        return view('ecommerce.productos.index', compact('categoria'));
    }

    public function producto(Producto $producto): View
    {
        $producto->load(['coloresStock.color', 'categoria']);

        return view('ecommerce.productos.show', compact('producto'));
    }

    public function carrito(): View
    {
        return view('ecommerce.carrito.index');
    }

    public function checkout(): View
    {
        return view('ecommerce.checkout.index');
    }

    public function confirmado(int $pedido): View
    {
        $pedidoModel = Pedido::query()->findOrFail($pedido);
        $pago = Pago::query()->where('pedido_id', $pedido)->latest('id')->first();

        return view('ecommerce.pedido-confirmado', [
            'pedidoId' => $pedido,
            'pedidoModel' => $pedidoModel,
            'pago' => $pago,
        ]);
    }
}
