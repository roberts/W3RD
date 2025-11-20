<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\TrackAuthenticationEntryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private TrackAuthenticationEntryAction $trackEntry
    ) {}

    /**
     * Authenticate user with email and password.
     *
     * POST /v1/auth/login
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'error' => 'INVALID_CREDENTIALS',
                'message' => 'The provided credentials are incorrect',
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken($request->ip() ?? 'api-token');

        $this->trackEntry->execute(
            $user,
            $token,
            $request->header('X-Client-Key'),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => UserResource::make($user),
        ]);
    }
}
