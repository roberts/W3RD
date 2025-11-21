<?php

namespace App\Http\Controllers\Api\V1\System;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(
        private SystemHealthService $healthService
    ) {}

    /**
     * Get system health status with service indicators.
     */
    public function __invoke(): JsonResponse
    {
        $health = $this->healthService->checkHealth();

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        return response()->json($health, $statusCode);
    }
}
