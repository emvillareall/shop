<?php

namespace App\Livewire\Ecommerce;

use App\Models\CategoriasProducto;
use App\Models\LineasRopa;
use App\Models\Producto;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class CatalogoProductos extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoria = '';
    public string $linea = '';
    public string $mode = 'catalog';

    public function mount(?int $categoriaId = null, ?int $lineaId = null, string $searchTerm = '', string $mode = 'catalog'): void
    {
        $this->mode = in_array($mode, ['catalog', 'home'], true) ? $mode : 'catalog';

        if ($lineaId) {
            $this->linea = (string) $lineaId;
        }

        if ($categoriaId) {
            $this->categoria = (string) $categoriaId;
        }

        $searchTerm = trim($searchTerm);
        if ($searchTerm !== '') {
            $this->search = $searchTerm;
        }
    }

    public function selectLinea(string $lineaId = ''): void
    {
        $this->linea = $lineaId;
        $this->categoria = '';
        $this->resetPage();
    }

    public function selectCategoria(string $categoriaId = ''): void
    {
        $this->categoria = $categoriaId;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->linea = '';
        $this->categoria = '';
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoria(): void
    {
        $this->resetPage();
    }

    public function updatingLinea(): void
    {
        $this->resetPage();
        $this->categoria = '';
    }

    public function render()
    {
        $lineaKey = $this->linea !== '' ? (int) $this->linea : 0;
        $categorias = Cache::remember(
            "shop:categorias:linea:{$lineaKey}",
            now()->addSeconds(45),
            fn () => CategoriasProducto::query()
                ->where('estado_categoria', 1)
                ->when($lineaKey > 0, fn ($q) => $q->where('linea_ropa_id', $lineaKey))
                ->orderBy('nombre_categoria')
                ->get(['id', 'nombre_categoria', 'linea_ropa_id'])
        );

        $lineas = Cache::remember(
            'shop:lineas:activas',
            now()->addMinutes(5),
            fn () => LineasRopa::query()
                ->where('estado_linea', 1)
                ->orderBy('nombre_linea')
                ->get(['id', 'nombre_linea'])
        );

        $productos = Producto::query()
            ->select([
                'id',
                'descripcion_producto',
                'codigo_producto',
                'precio_venta_producto',
                'precio_promocional',
                'promocion_activa',
                'promocion_fecha_inicio',
                'promocion_fecha_fin',
                'promocion_etiqueta',
                'categoria_producto_id',
                'stock_venta_producto',
                'imagen_producto',
                'estado_producto',
                'created_at',
            ])
            ->where('estado_producto', 1)
            ->with([
                'categoria:id,nombre_categoria,linea_ropa_id',
                'imagenes' => fn ($q) => $q->select('id', 'producto_id', 'color_id', 'color_normalizado', 'ruta', 'orden', 'es_principal', 'activo')
                    ->where('activo', true)
                    ->orderByDesc('es_principal')
                    ->orderBy('orden'),
            ])
            ->withSum('coloresStock as stock_total_variante', 'stock_por_color')
            ->withCount([
                'coloresStock as variantes_con_stock' => fn ($q) => $q->where('stock_por_color', '>', 0),
            ])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('descripcion_producto', 'like', '%' . $this->search . '%')
                        ->orWhere('codigo_producto', 'like', '%' . $this->search . '%')
                        ->orWhereHas('coloresStock', function ($cq) {
                            $cq->where('talla_por_color', 'like', '%' . $this->search . '%')
                                ->orWhereHas('color', function ($colorQ) {
                                    $colorQ->where('nombre_color', 'like', '%' . $this->search . '%');
                                });
                        });
                });
            })
            ->when($this->categoria, fn ($q) => $q->where('categoria_producto_id', $this->categoria))
            ->when($this->linea, fn ($query) => $query->whereHas('categoria', fn ($q) => $q->where('linea_ropa_id', (int) $this->linea)))
            ->latest('id')
            ->paginate(12);

        return view('livewire.ecommerce.catalogo-productos', compact('productos', 'categorias', 'lineas'));
    }
}
