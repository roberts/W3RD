<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to POST/PUT/DELETE
        if (! in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
            return $next($request);
        }

        $idempotencyKey = $request->header('X-Idempotency-Key');

        if (! $idempotencyKey) {
            return response()->json([
                'error' => 'MISSING_IDEMPOTENCY_KEY',
                'message' => 'X-Idempotency-Key header is required for this operation',
            ], 400);
        }

        // Validate key format (UUID v4 or ULID)
        if (! Uuid::isValid($idempotencyKey) && ! $this->isValidUlid($idempotencyKey)) {
            return response()->json([
                'error' => 'INVALID_IDEMPOTENCY_KEY',
                'message' => 'X-Idempotency-Key must be a valid UUID v4 or ULID',
            ], 400);
        }

        $redis = Redis::connection('idempotency');
        $cacheKey = $idempotencyKey;

        // Check for existing cached response
        $cachedResponse = $redis->get($cacheKey);
        if ($cachedResponse) {
            $data = json_decode($cachedResponse, true);

            return response()->json($data['body'], $data['status'])
                ->withHeaders($data['headers'] ?? []);
        }

        // Use distributed lock to prevent concurrent duplicate requests
        $lock = $redis->lock("lock:{$cacheKey}", 10);

        try {
            if (! $lock->get()) {
                // Another request with same key is processing
                return response()->json([
                    'error' => 'REQUEST_IN_PROGRESS',
                    'message' => 'A request with this idempotency key is currently being processed',
                ], 409);
            }

            // Process request
            $response = $next($request);

            // Cache successful response (2xx) for 24 hours
            if ($response->status() >= 200 && $response->status() < 300) {
                $redis->setex(
                    $cacheKey,
                    86400, // 24 hours
                    json_encode([
                        'status' => $response->status(),
                        'headers' => $response->headers->all(),
                        'body' => json_decode($response->getContent(), true),
                    ])
                );
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * Validate if the value is a valid ULID.
     */
    private function isValidUlid(string $value): bool
    {
        return preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value) === 1;
    }
}
