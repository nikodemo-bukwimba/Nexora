<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\Api\CreditController;
use Modules\Finance\Http\Controllers\Api\InvoiceController;
use Modules\Finance\Http\Controllers\Api\PaymentController;
use Modules\Finance\Http\Controllers\Api\PromotionController;
use Modules\Finance\Http\Controllers\Api\SubscriptionController;

/*
|--------------------------------------------------------------------------
| Finance API Routes
| Prefix  : /api/v1/finance
| Auth    : auth:sanctum on all routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('finance')->name('finance.')->group(function () {

    // ── Subscription plans (public list) ──────────────────────
    Route::get('plans', [SubscriptionController::class, 'plans'])->name('plans.index');

    // ── Org subscriptions ──────────────────────────────────────
    Route::get('orgs/{orgId}/subscription', [SubscriptionController::class, 'show'])->name('orgs.subscription.show');
    Route::post('orgs/{orgId}/subscription', [SubscriptionController::class, 'subscribe'])->name('orgs.subscription.subscribe');
    Route::patch('orgs/{orgId}/subscription/plan', [SubscriptionController::class, 'changePlan'])->name('orgs.subscription.change-plan');
    Route::post('orgs/{orgId}/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('orgs.subscription.cancel');
    Route::post('orgs/{orgId}/subscription/renew', [SubscriptionController::class, 'renew'])->name('orgs.subscription.renew');
    Route::get('orgs/{orgId}/subscription/check/{featureKey}', [SubscriptionController::class, 'checkLimit'])->name('orgs.subscription.check');

    // ── Invoices ───────────────────────────────────────────────
    Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('invoices/{id}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
    Route::post('invoices/{id}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::get('actors/{actorId}/invoices', [InvoiceController::class, 'forActor'])->name('actors.invoices');
    Route::get('orgs/{orgId}/invoices', [InvoiceController::class, 'forOrg'])->name('orgs.invoices');

    // ── Payments ───────────────────────────────────────────────
    Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('payments/{id}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('payments/{id}/complete', [PaymentController::class, 'complete'])->name('payments.complete');
    Route::post('payments/{id}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
    Route::get('actors/{actorId}/payments', [PaymentController::class, 'forActor'])->name('actors.payments');

    // ── Credit accounts ────────────────────────────────────────
    Route::get('actors/{actorId}/credit', [CreditController::class, 'show'])->name('actors.credit.show');
    Route::post('actors/{actorId}/credit/topup', [CreditController::class, 'topup'])->name('actors.credit.topup');
    Route::get('actors/{actorId}/credit/ledger', [CreditController::class, 'ledger'])->name('actors.credit.ledger');

    // ── Promotions ─────────────────────────────────────────────
    Route::get('orgs/{orgId}/promotions', [PromotionController::class, 'index'])->name('orgs.promotions.index');
    Route::post('orgs/{orgId}/promotions', [PromotionController::class, 'store'])->name('orgs.promotions.store');
    Route::post('promotions/validate', [PromotionController::class, 'validate'])->name('promotions.validate');
});
