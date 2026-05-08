<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "PRODUCTOS\n";
echo json_encode(Illuminate\Support\Facades\DB::table('productos')->select('id','descripcion_producto','stock_venta_producto')->orderByDesc('id')->limit(12)->get());
echo "\n\nVARIANTES_12\n";
echo json_encode(Illuminate\Support\Facades\DB::table('colores_productos')->where('producto_id',12)->get());
echo "\n\nVARIANTES_15\n";
echo json_encode(Illuminate\Support\Facades\DB::table('colores_productos')->where('producto_id',15)->get());
echo "\n\nQUERY_ENDPOINT_15\n";
echo json_encode(
Illuminate\Support\Facades\DB::table('colores_productos')
    ->leftJoin('colores', 'colores_productos.colores_id', '=', 'colores.id')
    ->where('colores_productos.producto_id', 15)
    ->where('colores_productos.talla_por_color', '!=', '')
    ->whereNotNull('colores_productos.talla_por_color')
    ->orderByRaw('COALESCE(colores.nombre_color, "") asc')
    ->orderBy('colores_productos.talla_por_color')
    ->select([
        'colores_productos.id',
        'colores_productos.producto_id',
        'colores_productos.colores_id',
        'colores_productos.talla_por_color',
        'colores_productos.stock_por_color',
        Illuminate\Support\Facades\DB::raw('COALESCE(colores.nombre_color, CONCAT("Color #", colores_productos.colores_id)) as nombre_color'),
        Illuminate\Support\Facades\DB::raw('COALESCE(colores.codigo_color, "#94a3b8") as codigo_color'),
    ])->get()
);
