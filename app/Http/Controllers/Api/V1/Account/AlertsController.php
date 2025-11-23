<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\MarkAlertsAsReadRequest;
use App\Http\Resources\AlertResource;
use App\Http\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertsController extends Controller
{
    use ApiResponses;

    /**
     * Get list of alerts for the authenticated user.
     *
     * GET /v1/account/alerts
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $alerts = $user->alerts()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

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

        // If specific alert ULIDs provided, mark those
        if (isset($validated['alert_ulids'])) {
            $user->alerts()
                ->whereIn('ulid', $validated['alert_ulids'])
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        } else {
            // Otherwise mark all unread alerts
            $user->alerts()
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return $this->messageResponse('Alerts marked as read.');
    }
}
