<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rematch\AcceptRematchRequest;
use App\Http\Requests\Rematch\DeclineRematchRequest;
use App\Http\Resources\RematchRequestResource;
use App\Http\Traits\ApiResponses;
use App\Models\Game\RematchRequest;
use App\Services\RematchService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RematchController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected RematchService $rematchService
    ) {}

    /**
     * Accept a rematch request.
     */
    public function accept(AcceptRematchRequest $request, RematchRequest $requestId): JsonResponse
    {
        $rematchRequest = $requestId;

        try {
            $newGame = $this->rematchService->acceptRematchRequest(
                $rematchRequest,
                $request->user()
            );

            $resourceData = RematchRequestResource::make($rematchRequest->fresh())->toArray($request);
            $resourceData['new_game_ulid'] = $newGame->ulid;

            return $this->successResponse($resourceData, 'Rematch accepted. New game created.');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Decline a rematch request.
     */
    public function decline(DeclineRematchRequest $request, RematchRequest $requestId): JsonResponse
    {
        $rematchRequest = $requestId;

        try {
            $this->rematchService->declineRematchRequest(
                $rematchRequest,
                $request->user()
            );

            return $this->successResponse(
                RematchRequestResource::make($rematchRequest->fresh()),
                'Rematch request declined'
            );
        } catch (AccessDeniedHttpException $e) {
            return $this->forbiddenResponse($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
