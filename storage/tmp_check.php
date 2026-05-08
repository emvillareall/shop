<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$rows = Illuminate\Support\Facades\DB::table('colores_productos')
    ->leftJoin('colores', 'colores_productos.colores_id', '=', 'colores.id')
    ->whereNotNull('colores_productos.talla_por_color')
    ->where('colores_productos.talla_por_color', '!=', '')
    ->select([
        'colores_productos.producto_id',
        'colores_productos.colores_id',
        'colores_productos.talla_por_color',
        'colores_productos.stock_por_color',
        Illuminate\Support\Facades\DB::raw('COALESCE(colores.nombre_color, CONCAT("Color #", colores_productos.colores_id)) as nombre_color'),
        Illuminate\Support\Facades\DB::raw('COALESCE(colores.codigo_color, "#94a3b8") as codigo_color'),
    ])
    ->get()
    ->groupBy('producto_id')
    ->map(fn($r) => $r->values())
    ->toArray();

echo json_encode(['count'=>count($rows),'keys'=>array_slice(array_keys($rows),0,20)]);
