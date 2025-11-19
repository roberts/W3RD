<?php

use App\Exceptions\AgentConfigurationException;
use App\Exceptions\GameAccessDeniedException;
use App\Exceptions\InvalidGameConfigurationException;
use App\Exceptions\LobbyInvitationException;
use App\Exceptions\LobbyStateException;
use App\Exceptions\PaymentValidationException;
use App\Exceptions\PlayerBusyException;
use App\Exceptions\RematchNotAvailableException;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (RematchNotAvailableException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        });

        $exceptions->render(function (PlayerBusyException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => array_filter([
                    'activity_type' => $e->activityType,
                    ...$e->context,
                ]),
            ], 409);
        });

        $exceptions->render(function (LobbyStateException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => array_filter([
                    'current_state' => $e->currentState,
                    ...$e->context,
                ]),
            ], 422);
        });

        $exceptions->render(function (GameAccessDeniedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => array_filter([
                    'game_ulid' => $e->gameUlid,
                    ...$e->context,
                ]),
            ], 403);
        });

        $exceptions->render(function (InvalidGameConfigurationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => array_filter([
                    'game_title' => $e->gameTitle,
                    ...$e->context,
                ]),
            ], 422);
        });

        $exceptions->render(function (LobbyInvitationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => array_filter([
                    'invitation_status' => $e->invitationStatus,
                    ...$e->context,
                ]),
            ], 422);
        });

        $exceptions->render(function (PaymentValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => array_filter([
                    'provider' => $e->provider,
                    ...$e->context,
                ]),
            ], 402);
        });

        $exceptions->render(function (ResourceNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => array_filter([
                    'resource_type' => $e->resourceType,
                    'resource_id' => $e->resourceId,
                    ...$e->context,
                ]),
            ], 404);
        });

        $exceptions->render(function (AgentConfigurationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => array_filter([
                    'agent_class' => $e->agentClass,
                    ...$e->context,
                ]),
            ], 500);
        });
    })->create();
