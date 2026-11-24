<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RecuperarContrasenaController;
use App\Http\Controllers\Auth\RestablecerContrasenaController;
use App\Http\Controllers\CarteraController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\DocumentoPdfController;
use App\Http\Controllers\EmpresaConfiguracionController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\PagoAbonoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Middleware\PermiteVistaEnMarco;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autenticación y recuperación de contraseña
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');

    Route::get('olvide-contrasena', [RecuperarContrasenaController::class, 'create'])->name('password.request');
    Route::post('olvide-contrasena', [RecuperarContrasenaController::class, 'store'])->middleware('throttle:recuperacion')->name('password.email');
    Route::get('restablecer-contrasena/{token}', [RestablecerContrasenaController::class, 'create'])->name('password.reset');
    Route::post('restablecer-contrasena', [RestablecerContrasenaController::class, 'store'])->middleware('throttle:recuperacion')->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Sistema contable (requiere sesión de un usuario activo)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'usuario.activo'])->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    // Dashboard principal
    Route::get('/', DashboardController::class)->name('dashboard');

    // Seguridad y Usuarios
    Route::resource('usuarios', UsuarioController::class)
        ->except(['show', 'destroy'])
        ->parameters(['usuarios' => 'usuario']);

    // Configuración de empresa (Administrador General)
    Route::get('configuracion/empresa', [EmpresaConfiguracionController::class, 'edit'])->name('empresa.edit');
    Route::put('configuracion/empresa', [EmpresaConfiguracionController::class, 'update'])->name('empresa.update');

    // Clientes
    Route::resource('clientes', ClienteController::class)
        ->parameters(['clientes' => 'cliente']);

    // Productos (catálogo de facturación)
    Route::resource('productos', ProductoController::class)
        ->except(['show'])
        ->parameters(['productos' => 'producto']);

    // Transacciones: ventas al contado / crédito y abonos
    Route::resource('facturas', FacturaController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->parameters(['facturas' => 'factura']);
    Route::patch('facturas/{factura}/anular', [FacturaController::class, 'anular'])->name('facturas.anular');

    Route::resource('abonos', PagoAbonoController::class)
        ->only(['create', 'store', 'destroy'])
        ->parameters(['abonos' => 'abono']);

    // Documentos: historial, cotizaciones y PDF
    Route::get('documentos', DocumentoController::class)->name('documentos.index');
    Route::resource('cotizaciones', CotizacionController::class)
        ->except(['index', 'destroy'])
        ->parameters(['cotizaciones' => 'cotizacion']);
    Route::get('facturas/{factura}/pdf', [DocumentoPdfController::class, 'factura'])->name('facturas.pdf');
    Route::get('cotizaciones/{cotizacion}/pdf', [DocumentoPdfController::class, 'cotizacion'])->name('cotizaciones.pdf');

    // Cartera y Alertas
    Route::get('cartera', [CarteraController::class, 'index'])->name('cartera.index');

    // Reportes avanzados (PDF y Excel)
    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('reportes/{tipo}/vista-previa/{formato}', [ReporteController::class, 'vistaPrevia'])
        ->whereIn('tipo', ['general', 'ventas', 'vendedores', 'cartera', 'estado-cuenta'])
        ->whereIn('formato', ['pdf', 'xlsx'])
        ->middleware(PermiteVistaEnMarco::class)
        ->name('reportes.vista-previa');
    Route::get('reportes/{tipo}/{formato}', [ReporteController::class, 'exportar'])
        ->whereIn('tipo', ['general', 'ventas', 'vendedores', 'cartera', 'estado-cuenta'])
        ->whereIn('formato', ['pdf', 'xlsx'])
        ->name('reportes.exportar');
});
