<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponses;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Get the authenticated user's public profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->resourceResponse(UserResource::make($user));
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->profileService->updateProfile(
            $user,
            $request->validated()
        );

        return $this->resourceResponse(
            UserResource::make($user->fresh()),
            'Profile updated successfully'
        );
    }
}
