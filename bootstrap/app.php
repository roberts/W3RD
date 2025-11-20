<?php

use App\Exceptions\AgentConfigurationException;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\CooldownActiveException;
use App\Exceptions\GameAccessDeniedException;
use App\Exceptions\GameActionDeniedException;
use App\Exceptions\InvalidActionDataException;
use App\Exceptions\InvalidGameConfigurationException;
use App\Exceptions\LobbyInvitationException;
use App\Exceptions\LobbyStateException;
use App\Exceptions\PaymentValidationException;
use App\Exceptions\PlayerBusyException;
use App\Exceptions\RateLimitExceededException;
use App\Exceptions\RematchNotAvailableException;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Global API error handler with correlation IDs
        $exceptions->render(function (\Throwable $e, $request) {
            // Only format JSON for API requests
            if (! $request->is('api/*')) {
                return null; // Let Laravel handle non-API exceptions normally
            }

            $correlationId = (string) Str::uuid();

            // Log all API errors with correlation ID
            \Log::error('API Error', [
                'correlation_id' => $correlationId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);

            // Return null to let specific exception handlers take precedence
            return null;
        });

        // Validation errors with standard format
        $exceptions->render(function (ValidationException $e, $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $correlationId = (string) Str::uuid();

            return response()->json([
                'error' => 'VALIDATION_FAILED',
                'message' => 'The request contains invalid data',
                'correlation_id' => $correlationId,
                'errors' => collect($e->errors())->map(fn ($messages, $field) => [
                    'field' => $field,
                    'code' => 'INVALID_VALUE',
                    'message' => $messages[0],
                ])->values()->all(),
            ], 422);
        });

        // HTTP exceptions with standard format
        $exceptions->render(function (HttpException $e, $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $correlationId = (string) Str::uuid();

            $errorCode = match ($e->getStatusCode()) {
                401 => 'UNAUTHORIZED',
                403 => 'FORBIDDEN',
                404 => 'NOT_FOUND',
                409 => 'CONFLICT',
                429 => 'RATE_LIMIT_EXCEEDED',
                503 => 'SERVICE_UNAVAILABLE',
                default => 'HTTP_ERROR',
            };

            return response()->json([
                'error' => $errorCode,
                'message' => $e->getMessage() ?: 'An error occurred',
                'correlation_id' => $correlationId,
            ], $e->getStatusCode());
        });

        // Business rule exceptions with standard format
        $exceptions->render(function (BusinessRuleException $e, $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $correlationId = (string) Str::uuid();

            return response()->json([
                'error' => $e->getErrorCode(),
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], $e->getStatusCode());
        });

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

        $exceptions->render(function (InvalidActionDataException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode,
                'game_title' => $e->gameTitle,
                'errors' => $e->context,
            ], 400);
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

        $exceptions->render(function (RateLimitExceededException $e) {
            $response = response()->json([
                'message' => $e->getMessage(),
                'errors' => array_filter([
                    'retry_after' => $e->retryAfter,
                    'limit' => $e->limit,
                    'window' => $e->window,
                    ...$e->context,
                ]),
            ], 429);

            // Add Retry-After header if available
            if ($e->retryAfter !== null) {
                $response->header('Retry-After', (string) $e->retryAfter);
            }

            return $response;
        });

        $exceptions->render(function (CooldownActiveException $e) {
            $response = response()->json([
                'message' => $e->getMessage(),
                'errors' => array_filter([
                    'reason' => $e->reason,
                    'retry_after' => $e->retryAfter,
                    ...$e->context,
                ]),
            ], 429);

            // Add Retry-After header
            if ($e->retryAfter !== null) {
                $response->header('Retry-After', (string) $e->retryAfter);
            }

            return $response;
        });

        $exceptions->render(function (GameActionDeniedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode,
                'game_title' => $e->gameTitle,
                'severity' => $e->severity,
                'retryable' => $e->isRetryable(),
                'errors' => $e->context,
            ], 422);
        });
    })->create();
