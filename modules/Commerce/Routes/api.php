<?php

use Illuminate\Support\Facades\Route;
use Modules\Commerce\Http\Controllers\Api\BasketController;
use Modules\Commerce\Http\Controllers\Api\OrderController;
use Modules\Commerce\Http\Controllers\Api\ProductController;

Route::middleware('auth:sanctum')->prefix('commerce')->name('commerce.')->group(function () {

    // ── Products ───────────────────────────────────────────────
    Route::get('orgs/{orgId}/products',        [ProductController::class, 'index'])->name('products.index');
    Route::post('orgs/{orgId}/products',        [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{id}',                 [ProductController::class, 'show'])->name('products.show');
    Route::patch('products/{id}',               [ProductController::class, 'update'])->name('products.update');
    Route::post('products/{id}/publish',        [ProductController::class, 'publish'])->name('products.publish');
    Route::post('products/{id}/archive',        [ProductController::class, 'archive'])->name('products.archive');

    // ── Basket ─────────────────────────────────────────────────
    Route::get('basket',                        [BasketController::class, 'show'])->name('basket.show');
    Route::post('basket/items',                 [BasketController::class, 'addItem'])->name('basket.items.add');
    Route::patch('basket/items/{variantId}',    [BasketController::class, 'updateItem'])->name('basket.items.update');
    Route::delete('basket/items/{variantId}',   [BasketController::class, 'removeItem'])->name('basket.items.remove');
    Route::post('basket/checkout',              [BasketController::class, 'checkout'])->name('basket.checkout');

    // ── Orders ─────────────────────────────────────────────────
    Route::get('orders/{id}',                           [OrderController::class, 'show'])->name('orders.show');
    Route::get('actors/{actorId}/orders',               [OrderController::class, 'forBuyer'])->name('orders.buyer');
    Route::get('orgs/{orgId}/orders',                   [OrderController::class, 'forSeller'])->name('orders.seller');
    Route::post('orders/{id}/confirm',                  [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('orders/{id}/processing',               [OrderController::class, 'markProcessing'])->name('orders.processing');
    Route::post('orders/{id}/ship',                     [OrderController::class, 'ship'])->name('orders.ship');
    Route::post('orders/{id}/deliver',                  [OrderController::class, 'deliver'])->name('orders.deliver');
    Route::post('orders/{id}/cancel',                   [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{id}/returns',                  [OrderController::class, 'requestReturn'])->name('orders.returns.request');
    Route::post('orders/{id}/returns/{returnId}/approve', [OrderController::class, 'approveReturn'])->name('orders.returns.approve');
});
