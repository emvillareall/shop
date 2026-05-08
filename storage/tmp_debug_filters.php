<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$total = Illuminate\Support\Facades\DB::table('pedidos')->count();
$espera = Illuminate\Support\Facades\DB::table('pedidos')->where('estado_url', 'EN ESPERA')->count();
$enviado = Illuminate\Support\Facades\DB::table('pedidos')->where('estado_url', 'ENVIADO')->count();

echo "TOTAL={$total}\n";
echo "EN_ESPERA={$espera}\n";
echo "ENVIADO={$enviado}\n";

$sample = Illuminate\Support\Facades\DB::table('pedidos')
    ->select('id', 'descripcion', 'estado_url', 'estado_pedido', 'estado_pago', 'estado_envio')
    ->orderBy('id')
    ->limit(10)
    ->get();

foreach ($sample as $row) {
    echo "{$row->id} | {$row->descripcion} | {$row->estado_url} | {$row->estado_pedido} | {$row->estado_pago} | {$row->estado_envio}\n";
}
