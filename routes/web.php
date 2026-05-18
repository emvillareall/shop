<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CategoriasProductoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ColoreController;
use App\Http\Controllers\ColoresProductoController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DetallePedidoController;
use App\Http\Controllers\EcommerceController;
use App\Http\Controllers\LineasRopaController;
use App\Http\Controllers\ApartadoController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ParametroController;
use App\Http\Controllers\PaymentFlowController;
use App\Http\Controllers\PaymentGatewayConfigController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProductoVarianteController;
use App\Http\Controllers\ProveedoreController;
use App\Http\Controllers\StockReservaMetricsController;
use App\Http\Controllers\TiendaController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

Route::get('/', function () {
    return view('landing');
});
Route::middleware('storefront')->group(function () {
    Route::get('/catalogo', [CatalogoController::class, 'catalogoGeneral'])->name('catalogo.general');
    Route::get('/catalogo/descargar', [CatalogoController::class, 'descargarCatalogoPDF'])->name('catalogo.descargar');
});

// Auth
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Public admin registration disabled
Route::get('register', function () {
    return redirect()->route('login')->with('error', 'El registro publico esta deshabilitado.');
})->name('register');
Route::post('register', function () {
    abort(403, 'Registro publico deshabilitado.');
});

// Password reset
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

// Verification
Route::get('email/verify', [VerificationController::class, 'show'])->name('verification.notice');
Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');
Route::post('email/resend', [VerificationController::class, 'resend'])->name('verification.resend');

// Admin/backoffice
Route::middleware(['auth'])->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::post('/home/venta-fisica', [App\Http\Controllers\HomeController::class, 'registrarVentaFisica'])->name('home.venta_fisica');

    Route::resource('clientes', ClienteController::class);
    Route::resource('tiendas', TiendaController::class);
    Route::resource('pedidos', PedidoController::class);
    Route::post('pedidos/{pedido}/confirmar-pago-social', [PedidoController::class, 'confirmarPagoSocial'])->name('pedidos.confirmar_pago_social');
    Route::resource('proveedores', ProveedoreController::class);
    Route::resource('productos', ProductoController::class);
    Route::get('lineas/{linea}/categorias', [ProductoController::class, 'categoriasPorLinea'])
        ->name('lineas.categorias');
    Route::resource('compras', CompraController::class);
    Route::post('compras/{compra}/ajustar-inventario', [CompraController::class, 'ajustarInventario'])->name('compras.ajustar_inventario');
    Route::post('compras/{compra}/anular', [CompraController::class, 'anular'])->name('compras.anular');
    Route::resource('parametros', ParametroController::class);
    Route::resource('detalle-pedidos', DetallePedidoController::class);
    Route::resource('colores-productos', ColoresProductoController::class);
    Route::resource('colores', ColoreController::class);
    Route::resource('categorias-productos', CategoriasProductoController::class);
    Route::resource('lineas-ropa', LineasRopaController::class);
    Route::get('pagos', [PagoController::class, 'index'])->name('pagos.index');
    Route::get('pagos/configuracion', [PaymentGatewayConfigController::class, 'index'])->name('pagos.configuracion.index');
    Route::post('pagos/configuracion/{gateway}', [PaymentGatewayConfigController::class, 'update'])->name('pagos.configuracion.update');
    Route::get('ventas', [VentaController::class, 'index'])->name('ventas.index');
    Route::get('apartados', [ApartadoController::class, 'index'])->name('apartados.index');
    Route::post('ventas/{venta}/abonos', [VentaController::class, 'registrarAbono'])->name('ventas.abonos.store');
    Route::post('pagos/{pago}/aprobar', [PagoController::class, 'aprobar'])->name('pagos.aprobar');
    Route::post('pagos/{pago}/rechazar', [PagoController::class, 'rechazar'])->name('pagos.rechazar');
    Route::get('admin/stock-reservas', [StockReservaMetricsController::class, 'index'])->name('admin.stock-reservas.index');
    Route::get('reportes', [App\Http\Controllers\ReportesController::class, 'index'])->name('reportes');
    Route::get('reportes/show/{id}', [App\Http\Controllers\ReportesController::class, 'show'])->name('show');
    Route::get('auditoria', [App\Http\Controllers\AuditLogController::class, 'index'])->name('auditoria.index');
    Route::get('auditoria/export-csv', [App\Http\Controllers\AuditLogController::class, 'exportCsv'])->name('auditoria.export_csv');
    Route::get('/admin/api/productos/{producto}/variantes', [ProductoVarianteController::class, 'admin'])
        ->middleware('throttle:120,1')
        ->name('admin.productos.variantes');

    // Legacy helpers disabled by design
    Route::get('/clientes/buscar/{cedula}', function () {
        return response()->json([
            'ok' => false,
            'message' => 'Busqueda legacy deshabilitada por seguridad.',
        ], 410);
    })->middleware('throttle:30,1');
    Route::post('/clientes/registrar', [ClienteController::class, 'registrar'])
        ->middleware('throttle:20,1');
});

// Signed PDFs
Route::middleware('auth')->group(function () {
    Route::get('/pdf_pedidos/{id}', function ($id) {
        $signed = URL::temporarySignedRoute('pdf.pedido.signed', now()->addMinutes(5), ['id' => $id]);
        return redirect()->to($signed);
    })->name('getPDF_pedidos');

    Route::get('/pdf_pedidos_completo/', function () {
        $signed = URL::temporarySignedRoute('pdf.pedidos.completo.signed', now()->addMinutes(5));
        return redirect()->to($signed);
    })->name('getPDF_pedidos_completo');

    Route::middleware('signed')->group(function () {
        Route::get('/secure/pdf_pedidos/{id}', [PDFController::class, 'getPDF_pedidos'])->name('pdf.pedido.signed');
        Route::get('/secure/pdf_pedidos_completo/', [PDFController::class, 'getPDF_pedidos_completo'])->name('pdf.pedidos.completo.signed');
    });

    Route::post('/estado_pedido/{id}', [PedidoController::class, 'cambiar_de_estado'])->name('estado_pedido');
});

Route::post('event/link-subscribe/{id}', [App\Http\Controllers\EventController::class, 'getLinkSubscribe'])
    ->middleware(['auth', 'throttle:20,1'])
    ->name('event.getLinkSubscribe');

Route::get('event/suscribirse/{id}', [App\Http\Controllers\EventController::class, 'subscribe'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('event.subscribe');
Route::post('event/suscribirse/{id}', [App\Http\Controllers\EventController::class, 'storeSubscribe'])
    ->middleware(['signed', 'throttle:20,1'])
    ->name('event.subscribe.store');

// Public ecommerce (modern only)
Route::middleware('storefront')->group(function () {
    Route::get('/shop', [EcommerceController::class, 'home'])->name('ecommerce.home');
    Route::get('/shop/catalogo', [EcommerceController::class, 'catalogo'])->name('ecommerce.productos.index');
    Route::get('/shop/categoria/{categoria}', [EcommerceController::class, 'categoria'])->name('ecommerce.categoria.show');
    Route::get('/shop/producto/{producto}', [EcommerceController::class, 'producto'])->name('ecommerce.productos.show');
    Route::get('/shop/api/productos/{producto}/variantes', [ProductoVarianteController::class, 'shop'])
        ->middleware('throttle:120,1')
        ->name('ecommerce.productos.variantes');
    Route::get('/shop/carrito', [EcommerceController::class, 'carrito'])->name('ecommerce.carrito.index');
    Route::get('/shop/checkout', [EcommerceController::class, 'checkout'])->name('ecommerce.checkout.index');
    Route::get('/shop/pedido-confirmado/{pedido}', [EcommerceController::class, 'confirmado'])->name('ecommerce.pedido.confirmado');
    Route::post('/shop/carrito/agregar', [EcommerceController::class, 'agregarCarrito'])->name('ecommerce.carrito.agregar');
    Route::post('/shop/carrito/eliminar/{index}', [EcommerceController::class, 'eliminarItemCarrito'])->name('ecommerce.carrito.eliminar');
    Route::post('/shop/carrito/vaciar', [EcommerceController::class, 'vaciarCarrito'])->name('ecommerce.carrito.vaciar');
});

// Media delivery from local DB-backed files
Route::get('/media/productos/{filename}', [MediaController::class, 'producto'])
    ->where('filename', '.*')
    ->name('media.producto');

// Payment callbacks
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/payments/{gateway}/{pago}/return', [PaymentFlowController::class, 'providerReturn'])->name('payments.return');
    Route::get('/payments/{gateway}/{pago}/cancel', [PaymentFlowController::class, 'providerCancel'])->name('payments.cancel');
});
Route::get('/checkout/payphone/{pago}', [PaymentFlowController::class, 'payphoneBox'])
    ->middleware(['storefront', 'throttle:60,1'])
    ->name('payments.payphone.box');

Route::post('/webhooks/paypal', [PaymentWebhookController::class, 'paypal'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.paypal');
Route::post('/webhooks/payphone', [PaymentWebhookController::class, 'payphone'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.payphone');
