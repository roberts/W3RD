<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alert\MarkAlertsAsReadRequest;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * Get list of alerts for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $alerts = $user->alerts()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        /** @var \Illuminate\Support\Collection<int, Alert> $alertItems */
        $alertItems = $alerts->getCollection();

        return response()->json([
            'data' => $alertItems->map(function (Alert $alert) {
                return [
                    'ulid' => $alert->ulid,
                    'type' => $alert->type,
                    'data' => $alert->data,
                    'read_at' => $alert->read_at?->toIso8601String(),
                    'created_at' => $alert->created_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
            ],
        ]);
    }

    /**
     * Mark alerts as read.
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

        return response()->json([
            'message' => 'Alerts marked as read.',
        ]);
    }
}
