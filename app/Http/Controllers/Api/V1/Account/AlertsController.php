<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\MarkAlertsAsReadAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ListAlertsRequest;
use App\Http\Requests\Account\MarkAlertsAsReadRequest;
use App\Http\Resources\Account\AlertResource;
use App\Http\Traits\ApiResponses;
use App\Services\Account\AlertQueryService;
use Illuminate\Http\JsonResponse;

class AlertsController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected AlertQueryService $alertQueryService,
        protected MarkAlertsAsReadAction $markAlertsAction
    ) {}

    /**
     * Get list of alerts for the authenticated user.
     *
     * GET /v1/account/alerts
     */
    public function index(ListAlertsRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $perPage = $validated['per_page'] ?? 20;
        $alerts = $this->alertQueryService
            ->buildUserAlertsQuery($user, $validated)
            ->paginate($perPage);

        return $this->collectionResponse(
            $alerts,
            fn ($items) => AlertResource::collection($items)
        );
    }

    /**
     * Mark alerts as read.
     *
     * POST /v1/account/alerts/read
     */
    public function markAsRead(MarkAlertsAsReadRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $count = $this->markAlertsAction->execute(
            $user,
            $validated['alert_ulids'] ?? null
        );

        return $this->messageResponse("$count alerts marked as read.");
    }
}
