<?php

use Illuminate\Support\Facades\Route;
use Modules\Platform\Http\Controllers\Api\ActorRelationshipController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('actors/{actorId}/relationships',                              [ActorRelationshipController::class, 'index'])->name('actors.relationships.index');
    Route::get('actors/{actorId}/relationships/type/{type}',                  [ActorRelationshipController::class, 'byType'])->name('actors.relationships.by-type');
    Route::post('actors/{actorId}/relationships/{relationshipId}/confirm',    [ActorRelationshipController::class, 'confirm'])->name('actors.relationships.confirm');
    Route::delete('actors/{actorId}/relationships/{relationshipId}',          [ActorRelationshipController::class, 'revoke'])->name('actors.relationships.revoke');
});
