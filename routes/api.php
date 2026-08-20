<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\InventoryMovementController;
use App\Http\Controllers\Api\Admin\InventoryReportController;
use App\Http\Controllers\Api\Admin\LicenseController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\ResetSystemController;
use App\Http\Controllers\Api\Admin\SedeController;
use App\Http\Controllers\Api\Admin\SellerController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashRegisterController;
use App\Http\Controllers\Api\PreferenceController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SaleReceiptController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/register', [AccountController::class, 'register'])->middleware('throttle:3,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::get('/preferences', [PreferenceController::class, 'show']);
    Route::put('/preferences', [PreferenceController::class, 'update']);
    Route::post('/account/activate', [AccountController::class, 'activate']);

    Route::middleware('account.active')->group(function () {
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('/dashboard', function () {
                return response()->json([
                    'message' => 'Bienvenido al panel del administrador.',
                ]);
            });

            Route::get('/licenses', [LicenseController::class, 'index']);
            Route::post('/licenses', [LicenseController::class, 'store']);
            Route::put('/licenses/{license}/status', [LicenseController::class, 'updateStatus']);

            Route::get('/sellers', [SellerController::class, 'index']);
            Route::post('/sellers', [SellerController::class, 'store']);
            Route::get('/sellers/{seller}', [SellerController::class, 'show']);
            Route::put('/sellers/{seller}', [SellerController::class, 'update']);
            Route::delete('/sellers/{seller}', [SellerController::class, 'destroy']);

            Route::get('/admins', [AdminUserController::class, 'index']);
            Route::post('/admins', [AdminUserController::class, 'store']);
            Route::put('/admins/{admin}', [AdminUserController::class, 'update']);
            Route::delete('/admins/{admin}', [AdminUserController::class, 'destroy']);

            Route::get('/sedes', [SedeController::class, 'index']);
            Route::post('/sedes', [SedeController::class, 'store']);
            Route::get('/sedes/{sede}', [SedeController::class, 'show']);
            Route::put('/sedes/{sede}', [SedeController::class, 'update']);

            Route::get('/categories', [CategoryController::class, 'index']);
            Route::post('/categories', [CategoryController::class, 'store']);
            Route::get('/categories/{category}', [CategoryController::class, 'show']);
            Route::put('/categories/{category}', [CategoryController::class, 'update']);

            Route::get('/products', [ProductController::class, 'index']);
            Route::post('/products', [ProductController::class, 'store']);
            Route::get('/products-next-code', [ProductController::class, 'nextCode']);
            Route::get('/products/{product}', [ProductController::class, 'show']);
            Route::put('/products/{product}', [ProductController::class, 'update']);

            Route::get('/inventory-movements', [InventoryMovementController::class, 'index']);
            Route::post('/inventory-movements', [InventoryMovementController::class, 'store']);

            Route::get('/inventory-reports/sede/{sede}', [InventoryReportController::class, 'bySede']);
            Route::get('/inventory-reports/sede/{sede}/pdf', [InventoryReportController::class, 'pdf']);

            Route::get('/activity-logs', [ActivityLogController::class, 'index']);
            Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show']);

            Route::get('/settings', [SettingController::class, 'index']);
            Route::post('/settings', [SettingController::class, 'upsert']);
            Route::get('/settings/{key}', [SettingController::class, 'showByKey']);

            Route::post('/reset-system', [ResetSystemController::class, 'reset']);
        });

        Route::middleware('role:admin,vendedor')->group(function () {
            Route::get('/sales/search-products', [SaleController::class, 'searchProducts']);
            Route::get('/sales', [SaleController::class, 'index']);
            Route::post('/sales', [SaleController::class, 'store']);
            Route::get('/sales/{sale}', [SaleController::class, 'show']);
            Route::post('/sales/{sale}/generate-receipt', [SaleReceiptController::class, 'generate']);

            Route::get('/cash-registers', [CashRegisterController::class, 'index']);
            Route::get('/cash-registers/{cashRegister}', [CashRegisterController::class, 'show']);
            Route::post('/cash-registers/open', [CashRegisterController::class, 'open']);
            Route::post('/cash-registers/close', [CashRegisterController::class, 'close']);

            Route::prefix('shared')->group(function () {
                Route::get('/ping', function () {
                    return response()->json([
                        'message' => 'Acceso permitido para administrador y vendedor.',
                    ]);
                });
            });
        });
    });
});

Route::get('/public/receipts/{token}', [SaleReceiptController::class, 'show'])
    ->whereUuid('token')
    ->name('public.receipts.show');
