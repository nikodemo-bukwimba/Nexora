<?php

use Illuminate\Support\Facades\Route;
use Modules\PharmaMarketing\Http\Controllers\Api\CustomerController;
use Modules\PharmaMarketing\Http\Controllers\Api\DailyReportController;
use Modules\PharmaMarketing\Http\Controllers\Api\FieldVisitController;
use Modules\PharmaMarketing\Http\Controllers\Api\ProductUpdateController;
use Modules\PharmaMarketing\Http\Controllers\Api\WeeklyPlanController;

Route::middleware('auth:sanctum')->prefix('pharma')->name('pharma.')->group(function () {

    // ── Customers ──────────────────────────────────────────────
    Route::get('orgs/{orgId}/customers',            [CustomerController::class, 'index'])->name('customers.index');
    Route::post('orgs/{orgId}/customers',            [CustomerController::class, 'store'])->name('customers.store');
    Route::get('customers/{id}',                    [CustomerController::class, 'show'])->name('customers.show');
    Route::patch('customers/{id}',                  [CustomerController::class, 'update'])->name('customers.update');
    Route::post('customers/{id}/assign',             [CustomerController::class, 'assign'])->name('customers.assign');
    Route::post('customers/{id}/contacts',           [CustomerController::class, 'addContact'])->name('customers.contacts.add');

    // ── Field Visits ───────────────────────────────────────────
    Route::get('orgs/{orgId}/visits',               [FieldVisitController::class, 'index'])->name('visits.index');
    Route::post('orgs/{orgId}/visits/check-in',     [FieldVisitController::class, 'checkIn'])->name('visits.check-in');
    Route::get('visits/{id}',                       [FieldVisitController::class, 'show'])->name('visits.show');
    Route::patch('visits/{id}/check-out',            [FieldVisitController::class, 'checkOut'])->name('visits.check-out');
    Route::post('visits/{id}/attachments',           [FieldVisitController::class, 'uploadAttachment'])->name('visits.attachments.upload');

    // ── Weekly Plans ───────────────────────────────────────────
    Route::get('orgs/{orgId}/plans',                [WeeklyPlanController::class, 'index'])->name('plans.index');
    Route::post('orgs/{orgId}/plans',               [WeeklyPlanController::class, 'store'])->name('plans.store');
    Route::get('plans/{id}',                        [WeeklyPlanController::class, 'show'])->name('plans.show');
    Route::post('plans/{id}/items',                 [WeeklyPlanController::class, 'addItem'])->name('plans.items.add');
    Route::post('plans/{id}/submit',                [WeeklyPlanController::class, 'submit'])->name('plans.submit');
    Route::post('plans/{id}/approve',               [WeeklyPlanController::class, 'approve'])->name('plans.approve');
    Route::post('plans/{id}/reject',                [WeeklyPlanController::class, 'reject'])->name('plans.reject');

    // ── Product Updates ────────────────────────────────────────
    Route::get('orgs/{orgId}/product-updates',      [ProductUpdateController::class, 'index'])->name('product-updates.index');
    Route::post('orgs/{orgId}/product-updates',     [ProductUpdateController::class, 'store'])->name('product-updates.store');
    Route::get('product-updates/{id}',              [ProductUpdateController::class, 'show'])->name('product-updates.show');
    Route::post('product-updates/{id}/publish',     [ProductUpdateController::class, 'publish'])->name('product-updates.publish');
    Route::get('product-updates/{id}/stats',        [ProductUpdateController::class, 'stats'])->name('product-updates.stats');

    // ── Daily Reports ──────────────────────────────────────────
    Route::get('orgs/{orgId}/reports',              [DailyReportController::class, 'index'])->name('reports.index');
    Route::get('reports/today',                     [DailyReportController::class, 'today'])->name('reports.today');
    Route::patch('reports/{id}',                    [DailyReportController::class, 'update'])->name('reports.update');
    Route::post('reports/{id}/submit',              [DailyReportController::class, 'submit'])->name('reports.submit');
    Route::post('reports/{id}/approve',             [DailyReportController::class, 'approve'])->name('reports.approve');
    Route::post('reports/{id}/reject',              [DailyReportController::class, 'reject'])->name('reports.reject');
});
