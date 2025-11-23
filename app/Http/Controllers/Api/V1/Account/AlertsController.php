<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ListAlertsRequest;
use App\Http\Requests\Account\MarkAlertsAsReadRequest;
use App\Http\Resources\AlertResource;
use App\Http\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

class AlertsController extends Controller
{
    use ApiResponses;

    /**
     * Get list of alerts for the authenticated user.
     *
     * GET /v1/account/alerts
     */
    public function index(ListAlertsRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $query = $user->alerts();

        // Apply filters
        if (isset($validated['read'])) {
            if ($validated['read']) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (isset($validated['date_from'])) {
            $query->where('created_at', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->where('created_at', '<=', $validated['date_to']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $alerts = $query->orderBy('created_at', 'desc')->paginate($perPage);

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
