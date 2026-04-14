<?php

use Illuminate\Support\Facades\Route;
use Modules\Logistics\Http\Controllers\Api\CourierController;
use Modules\Logistics\Http\Controllers\Api\DeliveryRunController;
use Modules\Logistics\Http\Controllers\Api\FleetController;
use Modules\Logistics\Http\Controllers\Api\TrackingController;
use Modules\Logistics\Http\Controllers\Api\ZoneRateController;

Route::middleware('auth:sanctum')->prefix('logistics')->name('logistics.')->group(function () {

    // ── Fleet — Vehicles ───────────────────────────────────────
    Route::get('orgs/{orgId}/vehicles',         [FleetController::class, 'indexVehicles'])->name('vehicles.index');
    Route::post('orgs/{orgId}/vehicles',         [FleetController::class, 'storeVehicle'])->name('vehicles.store');
    Route::patch('vehicles/{id}',               [FleetController::class, 'updateVehicle'])->name('vehicles.update');

    // ── Fleet — Drivers ────────────────────────────────────────
    Route::get('orgs/{orgId}/drivers',           [FleetController::class, 'indexDrivers'])->name('drivers.index');
    Route::post('orgs/{orgId}/drivers',          [FleetController::class, 'storeDriver'])->name('drivers.store');
    Route::post('drivers/availability',          [FleetController::class, 'updateAvailability'])->name('drivers.availability');

    // ── Zones & Rates ──────────────────────────────────────────
    Route::get('orgs/{orgId}/zones',             [ZoneRateController::class, 'indexZones'])->name('zones.index');
    Route::post('orgs/{orgId}/zones',            [ZoneRateController::class, 'storeZone'])->name('zones.store');
    Route::get('orgs/{orgId}/rates',             [ZoneRateController::class, 'indexRates'])->name('rates.index');
    Route::post('orgs/{orgId}/rates',            [ZoneRateController::class, 'storeRate'])->name('rates.store');
    Route::post('orgs/{orgId}/rates/preview',   [ZoneRateController::class, 'previewCost'])->name('rates.preview');

    // ── Delivery Runs ──────────────────────────────────────────
    Route::get('orgs/{orgId}/runs',              [DeliveryRunController::class, 'index'])->name('runs.index');
    Route::post('orgs/{orgId}/runs',             [DeliveryRunController::class, 'store'])->name('runs.store');
    Route::get('runs/{id}',                      [DeliveryRunController::class, 'show'])->name('runs.show');
    Route::post('runs/{id}/dispatch',            [DeliveryRunController::class, 'dispatch'])->name('runs.dispatch');
    Route::post('runs/{id}/start',              [DeliveryRunController::class, 'start'])->name('runs.start');
    Route::post('runs/{id}/stops',              [DeliveryRunController::class, 'addStop'])->name('runs.stops.add');
    Route::patch('runs/{id}/stops/reorder',     [DeliveryRunController::class, 'reorderStops'])->name('runs.stops.reorder');

    // ── Delivery Stops (driver actions) ────────────────────────
    Route::patch('stops/{stopId}/status',        [DeliveryRunController::class, 'updateStopStatus'])->name('stops.status');
    Route::post('stops/{stopId}/proof',          [DeliveryRunController::class, 'recordProof'])->name('stops.proof');

    // ── Third-party Couriers ───────────────────────────────────
    Route::get('orgs/{orgId}/couriers',          [CourierController::class, 'indexAccounts'])->name('couriers.index');
    Route::post('orgs/{orgId}/couriers',         [CourierController::class, 'storeAccount'])->name('couriers.store');
    Route::get('orgs/{orgId}/shipments',         [CourierController::class, 'indexShipments'])->name('shipments.index');
    Route::post('orgs/{orgId}/shipments',        [CourierController::class, 'bookShipment'])->name('shipments.book');
    Route::post('shipments/{id}/sync',           [CourierController::class, 'syncStatus'])->name('shipments.sync');

    // ── Order Tracking (any vertical can use this) ─────────────
    Route::get('track/{orderId}',                [TrackingController::class, 'trackOrder'])->name('track');
});
