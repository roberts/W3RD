<?php

namespace App\Actions\Auth;

use App\Models\Auth\Entry;
use App\Models\Auth\User;
use Laravel\Sanctum\NewAccessToken;

class TrackAuthenticationEntryAction
{
    /**
     * Track a user authentication entry.
     */
    public function execute(
        User $user,
        NewAccessToken $token,
        string|int|null $clientId,
        ?string $ipAddress,
        ?string $deviceInfo
    ): Entry {
        return Entry::create([
            'user_id' => $user->id,
            'client_id' => $clientId,
            'token_id' => $token->accessToken->id,
            'ip_address' => $ipAddress,
            'device_info' => $deviceInfo,
            'logged_in_at' => now(),
        ]);
    }
}
