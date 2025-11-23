<?php

namespace App\Services\Matchmaking;

use App\Http\Traits\ApiResponses;
use App\Matchmaking\Results\LobbyOperationResult;
use Illuminate\Http\JsonResponse;

class LobbyResponseMapper
{
    use ApiResponses;

    /**
     * Map an orchestrator result to an appropriate JSON response.
     */
    public function mapResultToResponse(LobbyOperationResult $result, ?string $successMessage = null, int $successCode = 200): JsonResponse
    {
        if ($result->success) {
            if ($successCode === 204) {
                return $this->noContentResponse();
            }

            if ($successCode === 201) {
                return $this->createdResponse(
                    null,
                    $successMessage ?? $result->message ?? 'Success'
                );
            }

            if ($successCode === 202) {
                return $this->dataResponse(
                    [],
                    $successMessage ?? $result->message ?? 'Success',
                    202
                );
            }

            return $this->messageResponse($successMessage ?? $result->message ?? 'Success');
        }

        // Determine status code based on error message patterns
        $statusCode = $this->determineErrorStatusCode($result->errorMessage);

        return $this->errorResponse($result->errorMessage, $statusCode);
    }

    /**
     * Determine the appropriate HTTP status code based on error message.
     */
    protected function determineErrorStatusCode(string $errorMessage): int
    {
        // Authorization errors
        if (str_contains($errorMessage, 'Only the host') || str_contains($errorMessage, 'not authorized')) {
            return 403;
        }

        // Not found errors
        if (str_contains($errorMessage, 'not found') || str_contains($errorMessage, 'not invited')) {
            return 404;
        }

        // Conflict errors
        if (str_contains($errorMessage, 'already')) {
            return 409;
        }

        // Default to 422 for validation/business rule violations
        return 422;
    }
}
