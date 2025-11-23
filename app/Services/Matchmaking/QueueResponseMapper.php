<?php

namespace App\Services\Matchmaking;

use App\Http\Traits\ApiResponses;
use App\Matchmaking\Results\QueueResult;
use Illuminate\Http\JsonResponse;

class QueueResponseMapper
{
    use ApiResponses;

    /**
     * Map a queue join result to an appropriate JSON response.
     */
    public function mapJoinResult(QueueResult $result): JsonResponse
    {
        $statusCode = $result->cooldownRemaining !== null ? 429 : 422;
        $errors = $result->context;

        // Add retry_after for cooldowns
        if ($result->cooldownRemaining !== null) {
            $errors['retry_after'] = $result->cooldownRemaining;
        }

        $response = $this->errorResponse(
            $result->errorMessage,
            $statusCode,
            null,
            $errors
        );

        // Add Retry-After header for rate limiting
        if ($result->cooldownRemaining !== null) {
            $response->header('Retry-After', (string) $result->cooldownRemaining);
        }

        return $response;
    }

    /**
     * Map a queue cancel result to an appropriate JSON response.
     */
    public function mapCancelResult(QueueResult $result): JsonResponse
    {
        return $this->errorResponse(
            $result->errorMessage,
            422,
            null,
            $result->context
        );
    }
}
