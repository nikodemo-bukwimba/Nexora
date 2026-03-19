<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\Api\InventoryController;
use Modules\Inventory\Http\Controllers\Api\StockAlertController;
use Modules\Inventory\Http\Controllers\Api\WarehouseController;

Route::middleware('auth:sanctum')->prefix('inventory')->name('inventory.')->group(function () {

    // ── Warehouses ─────────────────────────────────────────────
    Route::get('orgs/{orgId}/warehouses',         [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::post('orgs/{orgId}/warehouses',         [WarehouseController::class, 'store'])->name('warehouses.store');
    Route::get('warehouses/{id}',                  [WarehouseController::class, 'show'])->name('warehouses.show');
    Route::patch('warehouses/{id}',                [WarehouseController::class, 'update'])->name('warehouses.update');
    Route::post('warehouses/{id}/deactivate',      [WarehouseController::class, 'deactivate'])->name('warehouses.deactivate');

    // ── Stock receiving ────────────────────────────────────────
    Route::post('warehouses/{warehouseId}/receive', [InventoryController::class, 'receive'])->name('stock.receive');

    // ── Batches ────────────────────────────────────────────────
    Route::get('orgs/{orgId}/batches',             [InventoryController::class, 'listBatches'])->name('batches.index');
    Route::get('batches/{id}',                     [InventoryController::class, 'showBatch'])->name('batches.show');
    Route::post('batches/{id}/adjust',             [InventoryController::class, 'adjust'])->name('batches.adjust');
    Route::post('batches/{id}/transfer',           [InventoryController::class, 'transfer'])->name('batches.transfer');
    Route::post('batches/{id}/reserve',            [InventoryController::class, 'reserve'])->name('batches.reserve');
    Route::get('batches/{id}/movements',           [InventoryController::class, 'movements'])->name('batches.movements');

    // ── Product stock overview ─────────────────────────────────
    Route::get('orgs/{orgId}/products/{productId}/stock', [InventoryController::class, 'stockForProduct'])->name('products.stock');

    // ── Reservations ───────────────────────────────────────────
    Route::post('reservations/{id}/release',       [InventoryController::class, 'releaseReservation'])->name('reservations.release');
    Route::post('reservations/{id}/fulfill',       [InventoryController::class, 'fulfillReservation'])->name('reservations.fulfill');

    // ── Alerts ─────────────────────────────────────────────────
    Route::get('orgs/{orgId}/alerts',              [StockAlertController::class, 'index'])->name('alerts.index');
    Route::post('alerts/{id}/acknowledge',         [StockAlertController::class, 'acknowledge'])->name('alerts.acknowledge');
    Route::post('alerts/{id}/resolve',             [StockAlertController::class, 'resolve'])->name('alerts.resolve');
});
