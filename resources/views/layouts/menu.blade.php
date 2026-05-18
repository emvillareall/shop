@php
    $items = [
        ['route' => 'home', 'label' => 'Home', 'icon' => 'fa-solid fa-house'],
        ['route' => 'clientes.index', 'label' => 'Clientes', 'icon' => 'fa-solid fa-users'],
        ['route' => 'tiendas.index', 'label' => 'Tiendas', 'icon' => 'fa-solid fa-shop'],
        ['route' => 'pedidos.index', 'label' => 'Pedidos', 'icon' => 'fa-solid fa-truck-fast'],
        ['route' => 'proveedores.index', 'label' => 'Proveedores', 'icon' => 'fa-solid fa-boxes-stacked'],
        ['route' => 'categorias-productos.index', 'label' => 'Categorias', 'icon' => 'fa-solid fa-layer-group'],
        ['route' => 'colores.index', 'label' => 'Colores', 'icon' => 'fa-solid fa-palette'],
        ['route' => 'lineas-ropa.index', 'label' => 'Lineas Ropa', 'icon' => 'fa-solid fa-shirt'],
        ['route' => 'productos.index', 'label' => 'Productos', 'icon' => 'fa-solid fa-bag-shopping'],
        ['route' => 'compras.index', 'label' => 'Compras', 'icon' => 'fa-solid fa-receipt'],
        ['route' => 'ventas.index', 'label' => 'Ventas', 'icon' => 'fa-solid fa-cash-register'],
        ['route' => 'apartados.index', 'label' => 'Apartados', 'icon' => 'fa-solid fa-calendar-check'],
        ['route' => 'pagos.index', 'label' => 'Pagos', 'icon' => 'fa-solid fa-credit-card'],
        ['route' => 'pagos.configuracion.index', 'label' => 'Pasarelas', 'icon' => 'fa-solid fa-gears'],
        ['route' => 'admin.stock-reservas.index', 'label' => 'Reservas Stock', 'icon' => 'fa-solid fa-stopwatch'],
        ['route' => 'auditoria.index', 'label' => 'Auditoria', 'icon' => 'fa-solid fa-shield-halved'],
        ['route' => 'reportes', 'label' => 'Reportes', 'icon' => 'fa-solid fa-chart-line'],
    ];
@endphp

@foreach ($items as $item)
    @php
        $active = Request::routeIs($item['route']);
    @endphp
    <a href="{{ route($item['route']) }}"
       class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-700 hover:bg-white/70 hover:text-brand-700' }}">
        <i class="{{ $item['icon'] }} w-4 text-center"></i>
        <span>{{ $item['label'] }}</span>
    </a>
@endforeach

