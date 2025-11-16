<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GameActionController;
use App\Http\Controllers\Api\V1\GameRulesController;
use Illuminate\Support\Facades\Route;

// API Version 1
Route::prefix('v1')->group(function () {
    // Authentication Routes
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        // Public routes
        Route::post('register', 'register');
        Route::post('verify', 'verify');
        Route::post('login', 'login');
        Route::post('social', 'socialLogin');

        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', 'logout');
            Route::get('user', 'getUser');
            Route::patch('user', 'updateUser');
        });
    });

    // Game Rules API
    Route::get('/games/{gameTitle}/rules', [GameRulesController::class, 'show']);

    // Game Actions API (requires authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/games/{gameUlid}/action', [GameActionController::class, 'store']);
        Route::get('/games/{gameUlid}/available-actions', [GameActionController::class, 'availableActions']);
    });
});
