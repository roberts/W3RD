<?php

namespace App\Services\Account;

use App\Models\Auth\User;

class ProfileService
{
    /**
     * Update user profile.
     */
    /**
     * @param array<string, mixed> $data
     */
    public function updateProfile(User $user, array $data): User
    {
        // Update only provided fields
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['username'])) {
            $user->username = $data['username'];
        }

        if (array_key_exists('bio', $data)) {
            $user->bio = $data['bio'];
        }

        if (array_key_exists('social_links', $data)) {
            $user->social_links = $data['social_links'];
        }

        $user->save();

        return $user->fresh();
    }
}
