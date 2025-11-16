<?php

use App\Http\Controllers\Api\V1\GameActionController;
use App\Http\Controllers\Api\V1\GameRulesController;
use Illuminate\Support\Facades\Route;

// API Version 1
Route::prefix('v1')->group(function () {
    // Game Rules API
    Route::get('/games/{gameTitle}/rules', [GameRulesController::class, 'show']);

    // Game Actions API (requires authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/games/{gameUlid}/action', [GameActionController::class, 'store']);
        Route::get('/games/{gameUlid}/available-actions', [GameActionController::class, 'availableActions']);
    });
});
