<?php

use Illuminate\Support\Facades\Route;
use Modules\Commerce\Http\Controllers\Api\CategoryController;
use Modules\Commerce\Http\Controllers\Api\BasketController;
use Modules\Commerce\Http\Controllers\Api\BranchVariantPriceController;
use Modules\Commerce\Http\Controllers\Api\OrderController;
use Modules\Commerce\Http\Controllers\Api\ProductController;

Route::middleware('auth:sanctum')->prefix('commerce')->name('commerce.')->group(function () {

    // ── Categories ───────────────────────────────────────────────
    Route::get('orgs/{orgId}/categories',  [CategoryController::class, 'index'])->name('categories.index');
    Route::post('orgs/{orgId}/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/{id}',          [CategoryController::class, 'show'])->name('categories.show');
    Route::patch('categories/{id}',        [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{id}',       [CategoryController::class, 'destroy'])->name('categories.destroy');

    // ── Products ───────────────────────────────────────────────
    Route::get('orgs/{orgId}/products',        [ProductController::class, 'index'])->name('products.index');
    Route::post('orgs/{orgId}/products',       [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{id}',                [ProductController::class, 'show'])->name('products.show');
    Route::patch('products/{id}',              [ProductController::class, 'update'])->name('products.update');
    Route::post('products/{id}/publish',       [ProductController::class, 'publish'])->name('products.publish');
    Route::post('products/{id}/archive',       [ProductController::class, 'archive'])->name('products.archive');

    // ── Variants ───────────────────────────────────────────────
    Route::patch('variants/{variantId}',       [ProductController::class, 'updateVariant'])->name('variants.update');

    // ── Branch Variant Price Overrides ─────────────────────────
    Route::get('orgs/{orgId}/branch-prices',                    [BranchVariantPriceController::class, 'index'])->name('orgs.branch-prices.index');
    Route::put('orgs/{orgId}/variants/{variantId}/price',       [BranchVariantPriceController::class, 'upsert'])->name('orgs.variants.price.upsert');
    Route::delete('orgs/{orgId}/variants/{variantId}/price',    [BranchVariantPriceController::class, 'destroy'])->name('orgs.variants.price.destroy');

    // ── Basket ─────────────────────────────────────────────────
    Route::get('orgs/{orgId}/basket',                     [BasketController::class, 'show'])->name('basket.show');
    Route::post('orgs/{orgId}/basket/items',              [BasketController::class, 'addItem'])->name('basket.items.add');
    Route::patch('orgs/{orgId}/basket/items/{variantId}', [BasketController::class, 'updateItem'])->name('basket.items.update');
    Route::delete('orgs/{orgId}/basket/items/{variantId}',[BasketController::class, 'removeItem'])->name('basket.items.remove');
    Route::post('orgs/{orgId}/basket/checkout',           [BasketController::class, 'checkout'])->name('basket.checkout');

    // ── Orders ─────────────────────────────────────────────────
    Route::get('orders/{id}',                              [OrderController::class, 'show'])->name('orders.show');
    Route::get('actors/{actorId}/orders',                  [OrderController::class, 'forBuyer'])->name('orders.buyer');
    Route::get('orgs/{orgId}/orders',                      [OrderController::class, 'forSeller'])->name('orders.seller');
    Route::post('orders/{id}/confirm',                     [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('orders/{id}/processing',                  [OrderController::class, 'markProcessing'])->name('orders.processing');
    Route::post('orders/{id}/ship',                        [OrderController::class, 'ship'])->name('orders.ship');
    Route::post('orders/{id}/deliver',                     [OrderController::class, 'deliver'])->name('orders.deliver');
    Route::post('orders/{id}/cancel',                      [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{id}/returns',                     [OrderController::class, 'requestReturn'])->name('orders.returns.request');
    Route::post('orders/{id}/returns/{returnId}/approve',  [OrderController::class, 'approveReturn'])->name('orders.returns.approve');
    Route::post('orgs/{orgId}/orders/admin',               [OrderController::class, 'adminStore'])->name('orders.admin.store');
    Route::patch('orders/{id}',                            [OrderController::class, 'markPaid'])->name('orders.markPaid');
});