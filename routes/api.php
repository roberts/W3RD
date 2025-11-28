<?php

use App\Http\Controllers\Api\V1\Account\AlertsController;
use App\Http\Controllers\Api\V1\Account\ProfileController;
use App\Http\Controllers\Api\V1\Account\ProgressionController;
use App\Http\Controllers\Api\V1\Account\RecordsController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RefreshController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\Auth\VerifyController;
use App\Http\Controllers\Api\V1\Competitions\BracketController;
use App\Http\Controllers\Api\V1\Competitions\CompetitionController;
use App\Http\Controllers\Api\V1\Competitions\EntryController;
use App\Http\Controllers\Api\V1\Competitions\StandingsController;
use App\Http\Controllers\Api\V1\Competitions\StructureController;
use App\Http\Controllers\Api\V1\Economy\BalanceController;
use App\Http\Controllers\Api\V1\Economy\CashierController;
use App\Http\Controllers\Api\V1\Economy\PlanController;
use App\Http\Controllers\Api\V1\Economy\ReceiptController;
use App\Http\Controllers\Api\V1\Economy\SubscriptionController;
use App\Http\Controllers\Api\V1\Economy\TransactionController;
use App\Http\Controllers\Api\V1\Feeds\CasinoFloorController;
use App\Http\Controllers\Api\V1\Feeds\LeaderboardController;
use App\Http\Controllers\Api\V1\Feeds\LiveScoresController;
use App\Http\Controllers\Api\V1\Games\ActionsController;
use App\Http\Controllers\Api\V1\Games\GameAbandonController;
use App\Http\Controllers\Api\V1\Games\GameConcedeController;
use App\Http\Controllers\Api\V1\Games\GameController;
use App\Http\Controllers\Api\V1\Games\GameOutcomeController;
use App\Http\Controllers\Api\V1\Games\GameSyncController;
use App\Http\Controllers\Api\V1\Games\GameTimerController;
use App\Http\Controllers\Api\V1\Library\GameRulesController;
use App\Http\Controllers\Api\V1\Library\LibraryController;
use App\Http\Controllers\Api\V1\Matchmaking\LobbyController;
use App\Http\Controllers\Api\V1\Matchmaking\ProposalController;
use App\Http\Controllers\Api\V1\Matchmaking\QueueController;
use App\Http\Controllers\Api\V1\System\ConfigController;
use App\Http\Controllers\Api\V1\System\FeedbackController;
use App\Http\Controllers\Api\V1\System\HealthController;
use App\Http\Controllers\Api\V1\System\TimeController;
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
        Route::post('/feedback', FeedbackController::class);
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
        Route::get('/', [LibraryController::class, 'index']);
        Route::get('/{gameTitle}', [LibraryController::class, 'show']);
        Route::get('/{gameTitle}/rules', [GameRulesController::class, 'show']);
        Route::get('/{gameTitle}/entities', [LibraryController::class, 'entities']);
    });

    // ========================================
    // Authentication Routes
    // ========================================
    Route::prefix('auth')->group(function () {
        // Public routes
        Route::post('/register', RegisterController::class);
        Route::post('/verify', VerifyController::class);
        Route::post('/login', LoginController::class);
        Route::post('/social', SocialAuthController::class);

        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', LogoutController::class);
            Route::post('/refresh', RefreshController::class);
        });
    });

    // ========================================
    // Account Namespace - Profile & User Data
    // ========================================
    Route::middleware('auth:sanctum')->prefix('account')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::patch('/profile', [ProfileController::class, 'update']);
        Route::get('/progression', ProgressionController::class);
        Route::get('/records', RecordsController::class);
        Route::get('/alerts', [AlertsController::class, 'index']);
        Route::post('/alerts/read', [AlertsController::class, 'markAsRead']);
    });

    // ========================================
    // Matchmaking Namespace - Matchmaking & Lobbies
    // ========================================
    Route::middleware('auth:sanctum')->prefix('matchmaking')->group(function () {
        Route::controller(LobbyController::class)->prefix('lobbies')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{lobby:ulid}', 'show');
            Route::delete('/{lobby:ulid}', 'destroy');
            Route::post('/{lobby:ulid}/ready-check', 'readyCheck');
            Route::post('/{lobby:ulid}/seat', 'seat');
            Route::post('/{lobby:ulid}/players', 'invite');
            Route::put('/{lobby:ulid}/players/{username}', 'respond');
            Route::delete('/{lobby:ulid}/players/{username}', 'kick');
        });

        Route::post('/queue', [QueueController::class, 'store']);
        Route::delete('/queue/{slot:ulid}', [QueueController::class, 'destroy']);

        Route::post('/proposals', [ProposalController::class, 'store']);
        Route::post('/proposals/{proposal:ulid}/accept', [ProposalController::class, 'accept']);
        Route::post('/proposals/{proposal:ulid}/decline', [ProposalController::class, 'decline']);
    });

    // ========================================
    // Games Namespace - Active Game Management
    // ========================================
    Route::middleware('auth:sanctum')->prefix('games')->group(function () {
        Route::get('/', [GameController::class, 'index']);
        Route::get('/{game:ulid}', [GameController::class, 'show']);

        // Action submission with idempotency
        Route::post('/{game:ulid}/actions', [ActionsController::class, 'store'])
            ->middleware('idempotency');
        Route::get('/{game:ulid}/actions', [ActionsController::class, 'show']);
        Route::get('/{game:ulid}/options', [ActionsController::class, 'options']);

        // Timer and timeline information
        Route::get('/{game:ulid}/timer', [GameTimerController::class, 'show']);

        // Game exit options
        Route::post('/{game:ulid}/concede', [GameConcedeController::class, 'store']);
        Route::post('/{game:ulid}/abandon', [GameAbandonController::class, 'store']);

        // Outcome and sync
        Route::get('/{game:ulid}/outcome', [GameOutcomeController::class, 'show']);
        Route::get('/{game:ulid}/sync', [GameSyncController::class, 'show']);
    });

    // ========================================
    // Economy Namespace - Balance & Subscriptions
    // ========================================
    Route::middleware('auth:sanctum')->prefix('economy')->group(function () {
        Route::get('/balance', [BalanceController::class, 'index']);
        Route::get('/transactions', [TransactionController::class, 'index']);

        // Cashier operations (approved clients only, with idempotency)
        Route::post('/cashier', [CashierController::class, 'store'])
            ->middleware('idempotency');

        Route::get('/plans', [PlanController::class, 'index']);
        Route::post('/receipts/{provider}', [ReceiptController::class, 'store']);

        // Subscription management
        Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
        Route::get('/subscription', [SubscriptionController::class, 'show']);
        Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
    });

    // ========================================
    // Feeds Namespace - Real-Time Data Streams
    // ========================================
    Route::prefix('feeds')->group(function () {
        // SSE endpoints for live data
        Route::get('/games', [LiveScoresController::class, 'games']);
        Route::get('/wins', [LiveScoresController::class, 'wins']);
        Route::get('/leaderboards/{gameTitle}', [LeaderboardController::class, 'show']);
        Route::get('/tournaments', [CasinoFloorController::class, 'tournaments']);
        Route::get('/challenges', [CasinoFloorController::class, 'challenges']);
        Route::get('/achievements', [CasinoFloorController::class, 'achievements']);
    });

    // ========================================
    // Competitions Namespace - Tournaments
    // ========================================
    Route::prefix('competitions')->group(function () {
        Route::get('/', [CompetitionController::class, 'index']);
        Route::get('/{tournament:ulid}', [CompetitionController::class, 'show']);

        Route::middleware('auth:sanctum')->group(function () {
            // Entry with idempotency
            Route::post('/{tournament:ulid}/enter', [EntryController::class, 'store'])
                ->middleware('idempotency');

            Route::get('/{tournament:ulid}/structure', [StructureController::class, 'show']);
            Route::get('/{tournament:ulid}/bracket', [BracketController::class, 'show']);
            Route::get('/{tournament:ulid}/standings', [StandingsController::class, 'show']);
        });
    });
});
