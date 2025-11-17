<?php

namespace App\Services;

use App\Models\Auth\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    /**
     * Update user profile.
     *
     * @throws ValidationException
     */
    public function updateProfile(User $user, array $data): User
    {
        $validator = Validator::make($data, [
            'name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|nullable|string|max:500',
            'social_links' => 'sometimes|nullable|array',
            'social_links.twitter' => 'sometimes|nullable|url|max:255',
            'social_links.website' => 'sometimes|nullable|url|max:255',
            'social_links.discord' => 'sometimes|nullable|string|max:255',
            'social_links.twitch' => 'sometimes|nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        // Update only provided fields
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (array_key_exists('bio', $validated)) {
            $user->bio = $validated['bio'];
        }

        if (array_key_exists('social_links', $validated)) {
            $user->social_links = $validated['social_links'];
        }

        $user->save();

        return $user->fresh();
    }
}
