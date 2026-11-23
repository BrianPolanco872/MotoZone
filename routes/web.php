<?php

use App\Http\Controllers\Admin\CajaController as AdminCajaController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HomeCardController as AdminHomeCardController;
use App\Http\Controllers\Admin\HomeCategoryProductController as AdminHomeCategoryProductController;
use App\Http\Controllers\Admin\HomeContentController as AdminHomeContentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Pos\BitacoraController as PosBitacoraController;
use App\Http\Controllers\Pos\CashMovementController as PosCashMovementController;
use App\Http\Controllers\Pos\CustomerCreditController as PosCustomerCreditController;
use App\Http\Controllers\Pos\DailyCutController as PosDailyCutController;
use App\Http\Controllers\Pos\SaleController as PosSaleController;
use App\Http\Controllers\Pos\SaleReturnController as PosSaleReturnController;
use App\Http\Controllers\Pos\TicketSettingsController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'create'])->name('login');

Route::get('/home', HomeController::class)->name('home');

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

    Route::middleware('role:admin')->group(function () {
        Route::get('/tickets', [TicketSettingsController::class, 'edit'])->name('tickets.edit');
        Route::put('/tickets', [TicketSettingsController::class, 'update'])->name('tickets.update');
    });

    Route::middleware('permission:pos.sell')->group(function () {
        Route::post('/ventas', [PosSaleController::class, 'store'])->name('sales.store');
        Route::post('/ventas/{sale}/cobrar', [PosSaleController::class, 'charge'])->name('sales.charge');
    });

    Route::delete('/ventas/{sale}', [PosSaleController::class, 'cancel'])
        ->middleware(['permission:pos.sell', 'permission:pos.cancel.sale'])
        ->name('sales.cancel');

    Route::get('/ventas/pendientes', [PosSaleController::class, 'pending'])->name('sales.pending');
    Route::get('/ventas/pendientes/lista', [PosSaleController::class, 'pendingList'])->name('sales.pendingList');
    Route::get('/ventas/del-dia/lista', [PosSaleController::class, 'dayList'])->name('sales.dayList');
    Route::get('/ventas/del-dia', [PosSaleController::class, 'day'])->name('sales.day');
    Route::get('/ventas/anteriores', [PosSaleController::class, 'previous'])->name('sales.previous');
    Route::get('/ventas/{sale}', [PosController::class, 'show'])->name('sales.show');
    Route::get('/ventas/{sale}/ticket', [PosSaleController::class, 'ticket'])->name('sales.ticket');
    Route::get('/ventas/{sale}/ticket-data', [PosSaleController::class, 'ticketData'])->name('sales.ticketData');

    Route::middleware('permission:pos.returns.request')->group(function () {
        Route::get('/devoluciones', [PosSaleReturnController::class, 'index'])->name('returns.index');
        Route::get('/devoluciones/venta/{sale}', [PosSaleReturnController::class, 'create'])->name('returns.create');
    });

    Route::post('/devoluciones', [PosSaleReturnController::class, 'store'])
        ->middleware(['permission:pos.returns.request', 'permission:pos.return.do'])
        ->name('returns.store');

    Route::post('/ventas/{sale}/devolver-articulo/{saleItem}', [PosSaleController::class, 'returnItem'])
        ->middleware(['permission:pos.returns.request', 'permission:pos.return.do'])
        ->name('sales.returnItem');

    Route::put('/devoluciones/{saleReturn}/aprobar', [PosSaleReturnController::class, 'approve'])
        ->middleware('permission:pos.returns.approve')
        ->name('returns.approve');
    Route::put('/devoluciones/{saleReturn}/rechazar', [PosSaleReturnController::class, 'reject'])
        ->middleware('permission:pos.returns.approve')
        ->name('returns.reject');

    Route::middleware('permission:pos.cut.manage')->group(function () {
        Route::get('/bitacora', [PosBitacoraController::class, 'index'])->name('bitacora.index');
        Route::get('/bitacora/pdf', [PosBitacoraController::class, 'pdf'])->name('bitacora.pdf');
        Route::get('/corte', [PosDailyCutController::class, 'index'])->name('cuts.index');
        Route::post('/corte/{dailyCut}/efectivo-contado', [PosDailyCutController::class, 'recordCashCounted'])->name('cuts.record-cash');
        Route::get('/corte/imprimir', [PosDailyCutController::class, 'print'])->name('cuts.print');
        Route::get('/corte/pdf', [PosDailyCutController::class, 'pdf'])->name('cuts.pdf');
        Route::get('/corte/reporte-consolidado', [PosDailyCutController::class, 'consolidatedPdf'])->name('cuts.consolidated-pdf');
        Route::get('/corte/historial', [PosDailyCutController::class, 'history'])->name('cuts.history');
        Route::delete('/corte/{dailyCut}', [PosDailyCutController::class, 'destroy'])->name('cuts.destroy');
        Route::post('/corte/eliminar-seleccionados', [PosDailyCutController::class, 'destroySelected'])->name('cuts.destroy-selected');
        Route::delete('/corte/todos', [PosDailyCutController::class, 'destroyAll'])->name('cuts.destroyAll');
    });

    Route::post('/corte', [PosDailyCutController::class, 'store'])
        ->middleware(['auth', 'active', 'role_or_permission:pos.cut.manage|pos.cut.own'])
        ->name('cuts.store');

    Route::post('/corte/cerrar-dia', [PosDailyCutController::class, 'closeDay'])
        ->middleware(['auth', 'active', 'permission:pos.cut.manage'])
        ->name('cuts.close-day');

    Route::post('/corte/cerrar-mi-dia', [PosDailyCutController::class, 'closeMyDay'])
        ->middleware(['auth', 'active', 'role_or_permission:pos.cut.manage|pos.cut.own'])
        ->name('cuts.close-my-day');

    Route::get('/corte/detalle-abonos', [PosDailyCutController::class, 'customerPaymentsDetail'])
        ->middleware(['auth', 'active', 'role_or_permission:pos.cut.manage|pos.cut.own'])
        ->name('cuts.customer-payments-detail');

    Route::get('/corte/detalle-proveedores', [PosDailyCutController::class, 'supplierPaymentsDetail'])
        ->middleware(['auth', 'active', 'role_or_permission:pos.cut.manage|pos.cut.own'])
        ->name('cuts.supplier-payments-detail');

    Route::get('/mi-corte', [PosDailyCutController::class, 'mine'])
        ->middleware(['auth', 'active', 'role_or_permission:pos.cut.manage|pos.cut.own'])
        ->name('cuts.mine');

    Route::post('/corte/reabrir-mi-dia', [PosDailyCutController::class, 'reopenMyDay'])
        ->middleware(['auth', 'active', 'role_or_permission:pos.cut.manage|pos.cut.own'])
        ->name('cuts.reopen-my-day');
});

// Cartera manual: administradores y empleados activos, sin depender del acceso al POS.
Route::middleware(['auth', 'active', 'role:admin|empleado'])->prefix('pos')->name('pos.')->group(function () {
    Route::get('/abonos', [PosCustomerCreditController::class, 'index'])->name('abonos.index');
    Route::get('/abonos/productos', [PosCustomerCreditController::class, 'products'])->name('abonos.products');
    Route::post('/abonos', [PosCustomerCreditController::class, 'store'])->name('abonos.store');
    Route::post('/abonos/{customerCredit}/abonos', [PosCustomerCreditController::class, 'storePayment'])->name('abonos.payments.store');
});

// Caja de solo lectura: accesible con el permiso "ver módulo de caja".
Route::middleware(['auth', 'active', 'permission:pos.caja.view'])->prefix('pos')->name('pos.')->group(function () {
    Route::get('/caja', [PosCashMovementController::class, 'index'])->name('cash.index');
    Route::get('/caja/corte-empleados', [PosCashMovementController::class, 'cutBoard'])->name('cash.cut-board');
    Route::get('/caja/exportar', [PosCashMovementController::class, 'export'])->name('cash.export');
    Route::get('/caja/archivados', [PosCashMovementController::class, 'index'])->name('cash.archived');
});

// Caja de escritura: requiere el permiso completo de gestionar caja.
Route::middleware(['auth', 'active', 'permission:pos.cash.manage'])->prefix('pos')->name('pos.')->group(function () {
    Route::post('/caja/movimientos', [PosCashMovementController::class, 'store'])->name('cash.store');
    Route::post('/caja/{cashMovement}/archivar', [PosCashMovementController::class, 'archive'])->name('cash.archive');
    Route::post('/caja/archivar-masivo', [PosCashMovementController::class, 'archiveMass'])->name('cash.archive-mass');
    Route::post('/caja/{cashMovement}/restaurar', [PosCashMovementController::class, 'restore'])->name('cash.restore');
    Route::delete('/caja/{cashMovement}', [PosCashMovementController::class, 'destroy'])->name('cash.destroy');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active'])->group(function () {
    Route::get('/', AdminDashboardController::class)
        ->middleware('role:admin')
        ->name('dashboard');

    Route::middleware('role:admin')->group(function () {
        Route::get('/home', [AdminHomeContentController::class, 'edit'])->name('home.edit');
        Route::get('/home/vista-previa', [HomeController::class, 'preview'])->name('home.preview');
        Route::post('/home/restablecer', [AdminHomeContentController::class, 'reset'])->name('home.reset');
        Route::put('/home', [AdminHomeContentController::class, 'update'])->name('home.update');
        Route::get('/home/productos/buscar', [AdminHomeContentController::class, 'searchProducts'])->name('home.products.search');
        Route::post('/home/cards', [AdminHomeCardController::class, 'store'])->name('home.cards.store');
        Route::put('/home/cards/{card}', [AdminHomeCardController::class, 'update'])->name('home.cards.update');
        Route::delete('/home/cards/{card}', [AdminHomeCardController::class, 'destroy'])->name('home.cards.destroy');
        Route::post('/home/cards/{card}/mover', [AdminHomeCardController::class, 'move'])->name('home.cards.move');
        Route::post('/home/categorias/{card}/productos', [AdminHomeCategoryProductController::class, 'store'])->name('home.categories.products.store');
        Route::delete('/home/categorias/{card}/productos/{product}', [AdminHomeCategoryProductController::class, 'destroy'])->name('home.categories.products.destroy');
        Route::post('/home/categorias/{card}/productos/{product}/mover', [AdminHomeCategoryProductController::class, 'move'])->name('home.categories.products.move');
    });

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
        Route::get('/productos/componentes/buscar', [AdminProductController::class, 'components'])->name('products.components');
        Route::post('/productos', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('/productos/{product}/editar', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::put('/productos/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('/productos/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/productos/aplicar-precios', [AdminProductController::class, 'massUpdate'])->name('products.massUpdate');
        Route::post('/productos/limpiar-inventario', [AdminProductController::class, 'clearInventory'])->name('products.clearInventory');
        Route::post('/productos/importar', [AdminProductController::class, 'import'])->name('products.import');
        Route::get('/productos/exportar', [AdminProductController::class, 'export'])->name('products.export');
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

    // Caja de solo lectura en admin: accesible con el permiso "ver módulo de caja".
    Route::middleware(['auth', 'active', 'permission:pos.caja.view'])->name('caja.')->prefix('caja')->group(function () {
        // La caja se administra desde una sola pantalla (POS). Se conserva la URL por compatibilidad.
        Route::get('/', [AdminCajaController::class, 'index'])->name('index');
        Route::get('/corte', [AdminCajaController::class, 'corte'])->name('corte');
        Route::get('/exportar', [AdminCajaController::class, 'export'])->name('export');
    });

    // Caja de escritura en admin: requiere el permiso completo de gestionar caja.
    Route::middleware(['auth', 'active', 'permission:pos.cash.manage'])->name('caja.')->prefix('caja')->group(function () {
        Route::post('/movimientos', [AdminCajaController::class, 'store'])->name('movimientos.store');
        Route::delete('/{cashMovement}', [AdminCajaController::class, 'destroy'])->name('destroy');
        Route::match(['get', 'post'], '/archive/{cashMovement}', [AdminCajaController::class, 'archive'])->name('archive');
    });
});
