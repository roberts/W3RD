<?php

declare(strict_types=1);

namespace App\Services\Matchmaking;

use App\DataTransferObjects\Matchmaking\ProposalData;
use App\Matchmaking\Results\ProposalResult;
use Illuminate\Http\JsonResponse;

class ProposalResponseMapper
{
    /**
     * Map a ProposalResult to an HTTP response.
     */
    public function mapResultToResponse(ProposalResult $result, ?string $successMessage = null, int $successStatus = 200, int $errorStatus = 422): JsonResponse
    {
        if (! $result->success) {
            return response()->json([
                'error' => 'OPERATION_FAILED',
                'message' => $result->errorMessage,
                'errors' => $result->context,
            ], $errorStatus);
        }

        $data = ProposalData::fromModel($result->proposal);
        $message = $successMessage ?? 'Operation completed successfully';

        return response()->json([
            'data' => $data,
            'message' => $message,
        ], $successStatus);
    }
}
