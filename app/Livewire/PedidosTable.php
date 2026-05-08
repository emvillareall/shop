<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PedidosTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $estadoUrl = '';
    public string $estadoPedido = '';
    public string $estadoPago = '';
    public string $estadoEnvio = '';
    public bool $todayOnly = false;
    public int $perPage = 20;
    public ?string $urlSigned = null;
    public ?int $highlightPedidoId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'estadoUrl' => ['except' => ''],
        'estadoPedido' => ['except' => ''],
        'estadoPago' => ['except' => ''],
        'estadoEnvio' => ['except' => ''],
        'todayOnly' => ['except' => false],
        'perPage' => ['except' => 20],
    ];

    public function updated($name, $value): void
    {
        $this->resetPage();
    }

    public function updatedSearch($value): void
    {
        $this->search = trim((string) $value);
        $this->resetPage();
    }

    public function updatedEstadoUrl(): void
    {
        $this->resetPage();
    }

    public function updatedEstadoPedido(): void
    {
        $this->resetPage();
    }

    public function updatedEstadoPago(): void
    {
        $this->resetPage();
    }

    public function updatedEstadoEnvio(): void
    {
        $this->resetPage();
    }

    public function updatedTodayOnly(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function applyFilters(): void
    {
        $this->search = trim($this->search);
        $this->resetPage();
    }

    public function render()
    {
        $query = DB::table('pedidos')
            ->join('tiendas', 'tiendas.id', '=', 'pedidos.tienda_id')
            ->join('clientes', 'clientes.id', '=', 'pedidos.clientes_id')
            ->select(
                'pedidos.*',
                'clientes.nombres_clientes',
                'clientes.apellidos_clientes',
                'clientes.telefono_clientes',
                'tiendas.nombre_tienda',
                DB::raw("(select count(*) from detalle_pedidos dp where dp.pedido_id = pedidos.id and coalesce(dp.estado_dtpedidos, 1) = 1) as detalles_count"),
                DB::raw("(select p.estado from pagos p where p.pedido_id = pedidos.id order by p.id desc limit 1) as pago_registrado_estado"),
                DB::raw("(select p.metodo from pagos p where p.pedido_id = pedidos.id order by p.id desc limit 1) as pago_registrado_metodo")
            )
            ->where('pedidos.estado_pedidos', '1');

        if ($this->todayOnly) {
            $query->whereDate('pedidos.created_at', now()->toDateString());
        }

        if ($this->estadoUrl !== '') {
            $query->where('pedidos.estado_url', $this->estadoUrl);
        }
        if ($this->estadoPedido !== '') {
            $query->where('pedidos.estado_pedido', $this->estadoPedido);
        }
        if ($this->estadoPago !== '') {
            $query->where('pedidos.estado_pago', $this->estadoPago);
        }
        if ($this->estadoEnvio !== '') {
            $query->where('pedidos.estado_envio', $this->estadoEnvio);
        }

        if ($this->search !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('pedidos.descripcion', 'like', "%{$search}%")
                    ->orWhere('pedidos.codigo_pedido', 'like', "%{$search}%")
                    ->orWhere('pedidos.id', 'like', "%{$search}%")
                    ->orWhere('clientes.nombres_clientes', 'like', "%{$search}%")
                    ->orWhere('clientes.apellidos_clientes', 'like', "%{$search}%")
                    ->orWhere('clientes.cedula_clientes', 'like', "%{$search}%")
                    ->orWhere('tiendas.nombre_tienda', 'like', "%{$search}%");
            });
        }

        $pedidos = $query
            ->orderByDesc('pedidos.id')
            ->paginate($this->perPage);

        return view('livewire.pedidos-table', [
            'pedidos' => $pedidos,
        ]);
    }
}
