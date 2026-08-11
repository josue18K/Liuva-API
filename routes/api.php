<?php

use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\InventoryAdjustmentController;
use App\Http\Controllers\Api\Admin\InventoryReportController;
use App\Http\Controllers\Api\Admin\LicenseController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\SedeController;
use App\Http\Controllers\Api\Admin\SellerController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashRegisterController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SaleReceiptController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json([
                'message' => 'Bienvenido al panel del administrador.',
            ]);
        });

        Route::get('/licenses', [LicenseController::class, 'index']);
        Route::post('/licenses', [LicenseController::class, 'store']);

        Route::get('/sellers', [SellerController::class, 'index']);
        Route::post('/sellers', [SellerController::class, 'store']);
        Route::get('/sellers/{seller}', [SellerController::class, 'show']);
        Route::put('/sellers/{seller}', [SellerController::class, 'update']);

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
        Route::get('/products/{product}', [ProductController::class, 'show']);
        Route::put('/products/{product}', [ProductController::class, 'update']);

        Route::get('/inventory-adjustments', [InventoryAdjustmentController::class, 'index']);
        Route::post('/inventory-adjustments', [InventoryAdjustmentController::class, 'store']);
        Route::get('/inventory-adjustments/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'show']);
        Route::put('/inventory-adjustments/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'update']);
        Route::delete('/inventory-adjustments/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'destroy']);

        Route::get('/inventory-reports/sede/{sede}', [InventoryReportController::class, 'bySede']);

        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show']);

        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'upsert']);
        Route::get('/settings/{key}', [SettingController::class, 'showByKey']);
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

Route::get('/public/receipts/{sale}', [SaleReceiptController::class, 'show']);
