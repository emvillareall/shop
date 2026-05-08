<?php

namespace App\Livewire\Ecommerce;

use App\Models\CategoriasProducto;
use App\Models\LineasRopa;
use App\Models\Producto;
use Livewire\Component;
use Livewire\WithPagination;

class CatalogoProductos extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoria = '';
    public string $linea = '';

    public function mount(?int $categoriaId = null, ?int $lineaId = null): void
    {
        if ($lineaId) {
            $this->linea = (string) $lineaId;
        }

        if ($categoriaId) {
            $this->categoria = (string) $categoriaId;
        }
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
        $categorias = CategoriasProducto::query()
            ->where('estado_categoria', 1)
            ->when($this->linea, fn ($q) => $q->where('linea_ropa_id', $this->linea))
            ->orderBy('nombre_categoria')
            ->get();

        $lineas = LineasRopa::query()
            ->where('estado_linea', 1)
            ->orderBy('nombre_linea')
            ->get();

        $productos = Producto::query()
            ->where('estado_producto', 1)
            ->with('categoria')
            ->withSum('coloresStock as stock_total_variante', 'stock_por_color')
            ->withCount([
                'coloresStock as variantes_con_stock' => fn ($q) => $q->where('stock_por_color', '>', 0),
            ])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('descripcion_producto', 'like', '%' . $this->search . '%')
                        ->orWhere('codigo_producto', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->categoria, fn ($q) => $q->where('categoria_producto_id', $this->categoria))
            ->when($this->linea, function ($query) {
                $ids = CategoriasProducto::query()
                    ->where('linea_ropa_id', $this->linea)
                    ->pluck('id');
                $query->whereIn('categoria_producto_id', $ids);
            })
            ->latest('id')
            ->paginate(12);

        return view('livewire.ecommerce.catalogo-productos', compact('productos', 'categorias', 'lineas'));
    }
}
