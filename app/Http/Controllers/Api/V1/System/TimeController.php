<?php

namespace App\Http\Controllers\Api\V1\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TimeController extends Controller
{
    /**
     * Get authoritative server time for client synchronization.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'timestamp' => now()->timestamp,
            'iso8601' => now()->toIso8601String(),
            'timezone' => config('app.timezone'),
        ]);
    }
}
