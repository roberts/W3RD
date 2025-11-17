<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Game\RematchRequest;
use App\Services\RematchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RematchController extends Controller
{
    public function __construct(
        protected RematchService $rematchService
    ) {}

    /**
     * Accept a rematch request.
     */
    public function accept(Request $request, RematchRequest $requestId): JsonResponse
    {
        $rematchRequest = $requestId;

        try {
            $newGame = $this->rematchService->acceptRematchRequest(
                $rematchRequest,
                $request->user()
            );

            return response()->json([
                'data' => [
                    'rematch_request_ulid' => $rematchRequest->ulid,
                    'new_game_ulid' => $newGame->ulid,
                    'status' => $rematchRequest->fresh()->status,
                ],
                'message' => 'Rematch accepted. New game created.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Decline a rematch request.
     */
    public function decline(Request $request, RematchRequest $ulid): JsonResponse
    {
        $rematchRequest = $ulid;

        try {
            $this->rematchService->declineRematchRequest(
                $rematchRequest,
                $request->user()
            );

            return response()->json([
                'data' => [
                    'rematch_request_ulid' => $rematchRequest->ulid,
                    'status' => $rematchRequest->fresh()->status,
                ],
                'message' => 'Rematch request declined',
            ]);
        } catch (AccessDeniedHttpException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
