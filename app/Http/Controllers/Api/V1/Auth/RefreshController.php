<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefreshController extends Controller
{
    use ApiResponses;

    /**
     * Refresh the user's authentication token.
     *
     * POST /v1/auth/refresh
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke the current token
        $request->user()->currentAccessToken()->delete();

        // Create a new token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 31536000,
            'user' => [
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'level' => $user->level ?? 1,
                'xp' => $user->xp ?? 0,
            ],
        ], 200);
    }
}
