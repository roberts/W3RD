<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GameActionController;
use App\Http\Controllers\Api\V1\GameRulesController;
use App\Http\Controllers\Api\V1\LobbyController;
use App\Http\Controllers\Api\V1\LobbyPlayerController;
use App\Http\Controllers\Api\V1\QuickplayController;
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
        
        // Quickplay (Public Matchmaking)
        Route::prefix('games/quickplay')->controller(QuickplayController::class)->group(function () {
            Route::post('/', 'join');
            Route::delete('/', 'leave');
            Route::post('/accept', 'accept');
        });

        // Lobbies
        Route::prefix('games/lobbies')->group(function () {
            Route::get('/', [LobbyController::class, 'index']);
            Route::post('/', [LobbyController::class, 'store']);
            Route::get('/{lobby_ulid}', [LobbyController::class, 'show']);
            Route::delete('/{lobby_ulid}', [LobbyController::class, 'destroy']);
            Route::post('/{lobby_ulid}/ready-check', [LobbyController::class, 'readyCheck']);
            
            // Lobby Players
            Route::post('/{lobby_ulid}/players', [LobbyPlayerController::class, 'store']);
            Route::put('/{lobby_ulid}/players/{user_id}', [LobbyPlayerController::class, 'update']);
            Route::delete('/{lobby_ulid}/players/{user_id}', [LobbyPlayerController::class, 'destroy']);
        });
    });
});
