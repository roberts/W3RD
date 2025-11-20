<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SocialLoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Authenticate user with social provider token.
     *
     * POST /v1/auth/social
     */
    public function __invoke(SocialLoginRequest $request): JsonResponse
    {
        try {
            // Verify token with provider
            /** @phpstan-ignore-next-line */
            $providerUser = Socialite::driver($request->provider)
                ->userFromToken($request->access_token);

            // Find or create user and social account
            $result = $this->authService->handleSocialLogin(
                provider: $request->provider,
                providerId: $providerUser->getId(),
                providerToken: $providerUser->token,
                email: $providerUser->getEmail(),
                name: $providerUser->getName(),
                clientId: $request->client_id
            );

            return response()->json([
                'token' => $result['token'],
                'user' => UserResource::make($result['user']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'SOCIAL_AUTH_FAILED',
                'message' => 'Failed to authenticate with social provider',
            ], 401);
        }
    }
}
