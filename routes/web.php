<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\CatalogController;
use App\Models\Product;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ClientAppointmentController;
use App\Http\Controllers\VeterinaryAppointmentController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderHistoryController;
use App\Http\Controllers\OrderFulfillmentController;
use App\Http\Controllers\TestReportController;
use App\Http\Controllers\ClinicalRecordController;
use App\Http\Controllers\AdminDashboardController;

Route::get('/', function () {
    return Inertia::render('Home', [
        'featuredProducts' => Product::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('name')
            ->limit(8)
            ->get(),
    ]);
})->name('home');

Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');

Route::get('/carrito', [CartController::class, 'index'])->name('cart.index');
Route::post('/carrito/items', [CartController::class, 'add'])->name('cart.add');
Route::patch('/carrito/items/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/carrito/items/{product}', [CartController::class, 'remove'])->name('cart.remove');

Route::middleware('guest')->group(function () {
    Route::get('/acceso', [AuthController::class, 'createLogin'])->name('login');
    Route::post('/acceso', [AuthController::class, 'login'])->name('login.store');
    Route::get('/registro', [AuthController::class, 'createRegistration'])->name('register');
    Route::post('/registro', [AuthController::class, 'register'])->name('register.store');
    Route::get('/recuperar-contrasena', [AuthController::class, 'createPasswordRequest'])->name('password.request');
    Route::post('/recuperar-contrasena', [AuthController::class, 'requestPasswordReset'])->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [AuthController::class, 'createPasswordReset'])->name('password.reset');
    Route::post('/restablecer-contrasena', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/cuenta', [AuthController::class, 'account'])->name('account.show');
    Route::patch('/cuenta', [AuthController::class, 'updateAccount'])->name('account.update');
    Route::post('/salir', [AuthController::class, 'logout'])->name('logout');
    Route::get('/expedientes/clinicos/{appointment}.pdf', [ClinicalRecordController::class, 'download'])->name('clinical-records.download');
});

Route::middleware(['auth', 'role:cliente'])->group(function () {
    Route::post('/carrito/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::get('/cuenta/pedidos', [OrderHistoryController::class, 'index'])->name('orders.index');
});

Route::middleware(['auth', 'role:administrador'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/empleados', [EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/empleados', [EmployeeController::class, 'store'])->name('employees.store');
    Route::patch('/empleados/{employee}/estado', [EmployeeController::class, 'updateStatus'])->name('employees.status');
    Route::delete('/empleados/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::get('/pruebas', [TestReportController::class, 'index'])->name('testing.index');
});

Route::middleware(['auth', 'role:administrador,almacenista'])->prefix('almacen')->name('warehouse.')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::get('/picking', [OrderFulfillmentController::class, 'picking'])->name('picking');
    Route::patch('/picking/{order}/listo', [OrderFulfillmentController::class, 'markReady'])->name('picking.ready');
    Route::patch('/picking/{order}/despachar', [OrderFulfillmentController::class, 'dispatchShipment'])->name('picking.dispatch');
    Route::post('/productos', [InventoryController::class, 'storeProduct'])->name('products.store');
    Route::patch('/productos/{product}/ubicacion', [InventoryController::class, 'updateLocation'])->name('products.location');
    Route::post('/productos/{product}/lotes', [InventoryController::class, 'receive'])->name('products.receive');
    Route::post('/productos/{product}/salidas', [InventoryController::class, 'decrease'])->name('products.decrease');
    Route::delete('/productos/{product}', [InventoryController::class, 'retire'])->name('products.retire');
});

Route::middleware(['auth', 'role:cliente'])->prefix('citas')->name('appointments.')->group(function () {
    Route::get('/', [ClientAppointmentController::class, 'index'])->name('index');
    Route::post('/', [ClientAppointmentController::class, 'store'])->name('store');
    Route::patch('/{appointment}/cancelar', [ClientAppointmentController::class, 'cancel'])->name('cancel');
});

Route::middleware(['auth', 'role:veterinario'])->prefix('veterinaria/citas')->name('veterinary.appointments.')->group(function () {
    Route::get('/', [VeterinaryAppointmentController::class, 'index'])->name('index');
    Route::patch('/{appointment}/consulta', [VeterinaryAppointmentController::class, 'complete'])->name('complete');
    Route::patch('/{appointment}/cancelar', [VeterinaryAppointmentController::class, 'cancel'])->name('cancel');
    Route::patch('/{appointment}/inasistencia', [VeterinaryAppointmentController::class, 'markNoShow'])->name('no-show');
});

Route::middleware(['auth', 'role:administrador,cajero_pos'])->prefix('pos')->name('pos.')->group(function () {
    Route::get('/', [PosController::class, 'index'])->name('index');
    Route::post('/ventas', [PosController::class, 'store'])->name('sales.store');
    Route::get('/cierre-z', [PosController::class, 'close'])->name('close');
    Route::post('/cierre-z', [PosController::class, 'close'])->name('close.store');
    Route::get('/recolecciones', [OrderFulfillmentController::class, 'pickups'])->name('pickups.index');
    Route::patch('/recolecciones/{order}/entregar', [OrderFulfillmentController::class, 'deliver'])->name('pickups.deliver');
});
