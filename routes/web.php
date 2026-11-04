<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Pos\CashMovementController as PosCashMovementController;
use App\Http\Controllers\Pos\DailyCutController as PosDailyCutController;
use App\Http\Controllers\Pos\SaleController as PosSaleController;
use App\Http\Controllers\Pos\SaleReturnController as PosSaleReturnController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/producto/{product:slug}', [CatalogController::class, 'show'])->name('catalog.show');

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active', 'permission:pos.access'])->prefix('pos')->name('pos.')->group(function () {
    Route::get('/', [PosController::class, 'index'])->name('index');
    Route::get('/productos/buscar', [PosController::class, 'search'])->name('products.search');
    Route::get('/productos/frecuentes', [PosController::class, 'frequent'])->name('products.frequent');

    Route::middleware('permission:pos.sell')->group(function () {
        Route::post('/ventas', [PosSaleController::class, 'store'])->name('sales.store');
        Route::post('/ventas/{sale}/cobrar', [PosSaleController::class, 'charge'])->name('sales.charge');
    });

    Route::delete('/ventas/{sale}', [PosSaleController::class, 'cancel'])
        ->middleware(['permission:pos.sell', 'permission:pos.cancel.sale'])
        ->name('sales.cancel');

    Route::get('/ventas/pendientes', [PosSaleController::class, 'pending'])->name('sales.pending');
    Route::get('/ventas/del-dia', [PosSaleController::class, 'day'])->name('sales.day');
    Route::get('/ventas/anteriores', [PosSaleController::class, 'previous'])->name('sales.previous');
    Route::get('/ventas/{sale}', [PosController::class, 'show'])->name('sales.show');
    Route::get('/ventas/{sale}/ticket', [PosSaleController::class, 'ticket'])->name('sales.ticket');
    Route::get('/ventas/{sale}/ticket-data', [PosSaleController::class, 'ticketData'])->name('sales.ticketData');

    Route::middleware('permission:pos.cash.manage')->group(function () {
        Route::get('/caja', [PosCashMovementController::class, 'index'])->name('cash.index');
        Route::post('/caja/movimientos', [PosCashMovementController::class, 'store'])->name('cash.store');
        Route::post('/caja/{cashMovement}/archivar', [PosCashMovementController::class, 'archive'])->name('cash.archive');
        Route::post('/caja/archivar-masivo', [PosCashMovementController::class, 'archiveMass'])->name('cash.archive-mass');
        Route::post('/caja/{cashMovement}/restaurar', [PosCashMovementController::class, 'restore'])->name('cash.restore');
        Route::delete('/caja/{cashMovement}', [PosCashMovementController::class, 'destroy'])->name('cash.destroy');
        Route::get('/caja/exportar', [PosCashMovementController::class, 'export'])->name('cash.export');
        Route::get('/caja/archivados', [PosCashMovementController::class, 'index'])->name('cash.archived');
    });

    Route::middleware('permission:pos.returns.request')->group(function () {
        Route::get('/devoluciones', [PosSaleReturnController::class, 'index'])->name('returns.index');
        Route::get('/devoluciones/venta/{sale}', [PosSaleReturnController::class, 'create'])->name('returns.create');
    });

    Route::post('/devoluciones', [PosSaleReturnController::class, 'store'])
        ->middleware(['permission:pos.returns.request', 'permission:pos.return.do'])
        ->name('returns.store');

    Route::put('/devoluciones/{saleReturn}/aprobar', [PosSaleReturnController::class, 'approve'])
        ->middleware('permission:pos.returns.approve')
        ->name('returns.approve');
    Route::put('/devoluciones/{saleReturn}/rechazar', [PosSaleReturnController::class, 'reject'])
        ->middleware('permission:pos.returns.approve')
        ->name('returns.reject');

    Route::middleware('permission:pos.cut.manage')->group(function () {
        Route::get('/corte', [PosDailyCutController::class, 'index'])->name('cuts.index');
        Route::post('/corte', [PosDailyCutController::class, 'store'])->name('cuts.store');
        Route::post('/corte/{dailyCut}/efectivo-contado', [PosDailyCutController::class, 'recordCashCounted'])->name('cuts.record-cash');
        Route::get('/corte/imprimir', [PosDailyCutController::class, 'print'])->name('cuts.print');
        Route::get('/corte/historial', [PosDailyCutController::class, 'history'])->name('cuts.history');
        Route::delete('/corte/{dailyCut}', [PosDailyCutController::class, 'destroy'])->name('cuts.destroy');
        Route::delete('/corte/todos', [PosDailyCutController::class, 'destroyAll'])->name('cuts.destroyAll');
    });

    Route::get('/mi-corte', [PosDailyCutController::class, 'mine'])
        ->middleware(['permission:pos.sales.day.own', 'permission:pos.cut.own'])
        ->name('cuts.mine');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active'])->group(function () {
    Route::get('/', AdminDashboardController::class)
        ->middleware('role:admin')
        ->name('dashboard');

    // Perfil propio: disponible para cualquier usuario autenticado.
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');

    // El administrador edita los datos de sus empleados desde el mismo módulo.
    Route::put('/perfil/empleados/{user}', [ProfileController::class, 'updateEmployee'])
        ->middleware('role:admin')
        ->name('profile.employees.update');

    Route::middleware('permission:admin.products.manage')->group(function () {
        Route::get('/productos', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('/productos/crear', [AdminProductController::class, 'create'])->name('products.create');
        Route::post('/productos', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('/productos/{product}/editar', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::put('/productos/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('/productos/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/productos/aplicar-precios', [AdminProductController::class, 'massUpdate'])->name('products.massUpdate');
        Route::post('/productos/limpiar-inventario', [AdminProductController::class, 'clearInventory'])->name('products.clearInventory');
        Route::post('/productos/importar', [AdminProductController::class, 'import'])->name('products.import');
    });

    Route::middleware('permission:admin.users.manage')->group(function () {
        Route::get('/usuarios', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/usuarios/crear', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/usuarios', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/usuarios/{user}/permisos', [AdminUserController::class, 'updatePermissions'])->name('users.permissions.update');
        Route::get('/usuarios/{user}/editar', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/usuarios/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/usuarios/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    });
});
