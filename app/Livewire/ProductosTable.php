<?php

namespace App\Livewire;

use App\Models\Producto;
use Livewire\Component;
use Livewire\WithPagination;

class ProductosTable extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $productos = Producto::query()
            ->with(['coloresStock.color'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('codigo_producto', 'like', '%' . $this->search . '%')
                        ->orWhere('descripcion_producto', 'like', '%' . $this->search . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.productos-table', compact('productos'));
    }
}
