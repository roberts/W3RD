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

        $newGame = $this->handleServiceCall(
            fn () => $this->rematchService->acceptRematchRequest(
                $rematchRequest,
                $request->user()
            ),
            'Failed to accept rematch request'
        );

        if ($newGame instanceof JsonResponse) {
            return $newGame;
        }

        $resourceData = RematchRequestResource::make($rematchRequest->fresh())->toArray($request);
        $resourceData['new_game_ulid'] = $newGame->ulid;

        return $this->dataResponse($resourceData, 'Rematch accepted. New game created.');
    }

    /**
     * Decline a rematch request.
     */
    public function decline(DeclineRematchRequest $request, RematchRequest $requestId): JsonResponse
    {
        $rematchRequest = $requestId;

        $result = $this->handleServiceCall(
            function () use ($rematchRequest, $request) {
                $this->rematchService->declineRematchRequest(
                    $rematchRequest,
                    $request->user()
                );

                return true;
            },
            'Failed to decline rematch request',
            403
        );

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return $this->resourceResponse(
            RematchRequestResource::make($rematchRequest->fresh()),
            'Rematch request declined'
        );
    }
}
