<?php

use App\Http\Controllers\Api\V1\AlertController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\GameActionController;
use App\Http\Controllers\Api\V1\GameController;
use App\Http\Controllers\Api\V1\LeaderboardController;
use App\Http\Controllers\Api\V1\Library\GameLibraryController;
use App\Http\Controllers\Api\V1\Library\GameRulesController;
use App\Http\Controllers\Api\V1\LobbyController;
use App\Http\Controllers\Api\V1\LobbyPlayerController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\QuickplayController;
use App\Http\Controllers\Api\V1\RematchController;
use App\Http\Controllers\Api\V1\System\ConfigController;
use App\Http\Controllers\Api\V1\System\HealthController;
use App\Http\Controllers\Api\V1\System\TimeController;
use App\Http\Controllers\Api\V1\UserLevelsController;
use App\Http\Controllers\Api\V1\UserStatsController;
use App\Http\Controllers\Api\V1\Webhooks\WebhookController;
use Illuminate\Support\Facades\Route;

// API Version 1
Route::prefix('v1')->group(function () {
    // ========================================
    // System Namespace - Health & Configuration
    // ========================================
    Route::prefix('system')->group(function () {
        Route::get('/health', HealthController::class);
        Route::get('/time', TimeController::class);
        Route::get('/config', ConfigController::class);
    });

    // ========================================
    // Webhooks Namespace - External Provider Events
    // ========================================
    Route::prefix('webhooks')->group(function () {
        Route::post('/{provider}', WebhookController::class);
    });

    // ========================================
    // Library Namespace - Game Discovery & Rules
    // ========================================
    Route::prefix('library')->group(function () {
        Route::get('/', [GameLibraryController::class, 'index']);
        Route::get('/{key}', [GameLibraryController::class, 'show']);
        Route::get('/{key}/rules', [GameRulesController::class, 'show']);
        Route::get('/{key}/entities', [GameLibraryController::class, 'entities']);
    });

    // ========================================
    // Authentication Routes
    // ========================================
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

    // ========================================
    // Legacy Routes (will be migrated to namespaces)
    // ========================================
    Route::get('/leaderboard/{gameTitle}', [LeaderboardController::class, 'show']);

    // Gamer Protocol (requires authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // Billing
        Route::prefix('billing')->controller(BillingController::class)->group(function () {
            Route::get('/plans', 'getPlans');
            Route::get('/status', 'getStatus');
            Route::post('/subscribe', 'createStripeSubscription');
            Route::get('/manage', 'manageSubscription');
            Route::post('/apple/verify', 'verifyAppleReceipt');
            Route::post('/google/verify', 'verifyGoogleReceipt');
            Route::post('/telegram/verify', 'verifyTelegramReceipt');
        });

        // Personal User Endpoints
        Route::prefix('me')->group(function () {
            Route::get('/profile', [ProfileController::class, 'show']);
            Route::patch('/profile', [ProfileController::class, 'update']);
            Route::get('/stats', [UserStatsController::class, 'show']);
            Route::get('/levels', [UserLevelsController::class, 'show']);
            Route::get('/alerts', [AlertController::class, 'index']);
            Route::post('/alerts/mark-as-read', [AlertController::class, 'markAsRead']);
        });

        // Game Routes
        Route::prefix('games')->group(function () {
            // Quickplay (Public Matchmaking) - must be before /games/{gameUlid}
            Route::prefix('quickplay')->controller(QuickplayController::class)->group(function () {
                Route::post('/', 'join');
                Route::delete('/', 'leave');
                Route::post('/accept', 'accept');
            });

            // Rematch Requests
            Route::post('/rematch/{requestId}/accept', [RematchController::class, 'accept']);
            Route::post('/rematch/{requestId}/decline', [RematchController::class, 'decline']);

            // Lobbies - must be before /games/{gameUlid}
            Route::prefix('lobbies')->group(function () {
                Route::get('/', [LobbyController::class, 'index']);
                Route::post('/', [LobbyController::class, 'store']);
                Route::get('/{lobby_ulid}', [LobbyController::class, 'show']);
                Route::delete('/{lobby_ulid}', [LobbyController::class, 'destroy']);
                Route::post('/{lobby_ulid}/ready-check', [LobbyController::class, 'readyCheck']);

                // Lobby Players
                Route::post('/{lobby_ulid}/players', [LobbyPlayerController::class, 'store']);
                Route::put('/{lobby_ulid}/players/{username}', [LobbyPlayerController::class, 'update']);
                Route::delete('/{lobby_ulid}/players/{username}', [LobbyPlayerController::class, 'destroy']);
            });

            // Games
            Route::get('/', [GameController::class, 'index']);
            Route::get('/{gameUlid}', [GameController::class, 'show']);
            Route::post('/{gameUlid}/action', [GameActionController::class, 'store']);
            Route::get('/{gameUlid}/options', [GameActionController::class, 'options']);
            Route::get('/{gameUlid}/history', [GameController::class, 'history']);
            Route::post('/{gameUlid}/forfeit', [GameController::class, 'forfeit']);
            Route::post('/{gameUlid}/rematch', [GameController::class, 'requestRematch']);
        });
    });
});
