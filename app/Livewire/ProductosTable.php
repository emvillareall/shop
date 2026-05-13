<?php

namespace App\Livewire;

use App\Models\CategoriasProducto;
use App\Models\LineasRopa;
use App\Models\Producto;
use Livewire\Component;
use Livewire\WithPagination;

class ProductosTable extends Component
{
    use WithPagination;

    // Filtros de entrada (UI)
    public string $search = '';
    public string $linea = '';
    public string $categoria = '';
    public string $estadoStock = '';
    public string $precioMin = '';
    public string $precioMax = '';

    // Filtros aplicados (query real)
    public string $appliedSearch = '';
    public string $appliedLinea = '';
    public string $appliedCategoria = '';
    public string $appliedEstadoStock = '';
    public string $appliedPrecioMin = '';
    public string $appliedPrecioMax = '';

    protected $queryString = [
        'appliedSearch' => ['except' => ''],
        'appliedLinea' => ['except' => ''],
        'appliedCategoria' => ['except' => ''],
        'appliedEstadoStock' => ['except' => ''],
        'appliedPrecioMin' => ['except' => ''],
        'appliedPrecioMax' => ['except' => ''],
    ];

    public function mount(): void
    {
        // Si llega por querystring (back/forward), reflejar en UI.
        $this->search = $this->appliedSearch;
        $this->linea = $this->appliedLinea;
        $this->categoria = $this->appliedCategoria;
        $this->estadoStock = $this->appliedEstadoStock;
        $this->precioMin = $this->appliedPrecioMin;
        $this->precioMax = $this->appliedPrecioMax;
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'search', 'linea', 'categoria', 'estadoStock', 'precioMin', 'precioMax',
            'appliedSearch', 'appliedLinea', 'appliedCategoria', 'appliedEstadoStock', 'appliedPrecioMin', 'appliedPrecioMax',
        ]);
        $this->resetPage();
    }

    public function aplicarFiltros(): void
    {
        $search = trim($this->search);
        $linea = trim($this->linea);
        $categoria = trim($this->categoria);
        $estadoStock = trim($this->estadoStock);
        $precioMin = str_replace(',', '.', trim((string) $this->precioMin));
        $precioMax = str_replace(',', '.', trim((string) $this->precioMax));

        if ($precioMin !== '' && $precioMax !== ''
            && is_numeric($precioMin) && is_numeric($precioMax)
            && (float) $precioMin > (float) $precioMax) {
            [$precioMin, $precioMax] = [$precioMax, $precioMin];
        }

        $this->appliedSearch = $search;
        $this->appliedLinea = $linea;
        $this->appliedCategoria = $categoria;
        $this->appliedEstadoStock = $estadoStock;
        $this->appliedPrecioMin = $precioMin;
        $this->appliedPrecioMax = $precioMax;

        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->appliedSearch);
        $linea = trim($this->appliedLinea);
        $categoria = trim($this->appliedCategoria);
        $estadoStock = trim($this->appliedEstadoStock);
        $precioMin = str_replace(',', '.', trim((string) $this->appliedPrecioMin));
        $precioMax = str_replace(',', '.', trim((string) $this->appliedPrecioMax));

        $productos = Producto::query()
            ->with(['coloresStock.color', 'categoria.linea'])
            ->withSum('coloresStock as stock_variante_total', 'stock_por_color')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('codigo_producto', 'like', '%' . $search . '%')
                        ->orWhere('descripcion_producto', 'like', '%' . $search . '%');
                });
            })
            ->when($linea !== '', function ($query) use ($linea) {
                $lineaId = (int) $linea;
                $query->whereHas('categoria', fn ($q) => $q->where('linea_ropa_id', $lineaId));
            })
            ->when($categoria !== '', function ($query) use ($categoria) {
                $query->where('categoria_producto_id', (int) $categoria);
            })
            ->when($precioMin !== '' && is_numeric($precioMin), function ($query) use ($precioMin) {
                $query->where('precio_venta_producto', '>=', (float) $precioMin);
            })
            ->when($precioMax !== '' && is_numeric($precioMax), function ($query) use ($precioMax) {
                $query->where('precio_venta_producto', '<=', (float) $precioMax);
            })
            ->when($estadoStock !== '', function ($query) use ($estadoStock) {
                $stockExpr = '(SELECT COALESCE(SUM(cp.stock_por_color),0) FROM colores_productos cp WHERE cp.producto_id = productos.id)';
                if ($estadoStock === 'agotado') {
                    $query->whereRaw("$stockExpr <= 0");
                } elseif ($estadoStock === 'poco') {
                    $query->whereRaw("$stockExpr > 0 AND $stockExpr <= 5");
                } elseif ($estadoStock === 'disponible') {
                    $query->whereRaw("$stockExpr > 5");
                }
            })
            ->orderByDesc('id')
            ->paginate(15);

        $lineas = LineasRopa::query()->orderBy('nombre_linea')->get(['id', 'nombre_linea']);
        $categorias = CategoriasProducto::query()
            ->when(trim($this->linea) !== '', fn ($q) => $q->where('linea_ropa_id', (int) $this->linea))
            ->orderBy('nombre_categoria')
            ->get(['id', 'nombre_categoria', 'linea_ropa_id']);

        return view('livewire.productos-table', compact('productos', 'lineas', 'categorias'));
    }
}
