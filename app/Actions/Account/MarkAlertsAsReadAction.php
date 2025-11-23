<?php

namespace App\Actions\Account;

use App\Models\Auth\User;

class MarkAlertsAsReadAction
{
    /**
     * Mark alerts as read for a user.
     *
     * @param  User  $user  The user whose alerts to mark
     * @param  array|null  $alertUlids  Specific alert ULIDs to mark, or null for all unread
     * @return int The number of alerts marked as read
     */
    public function execute(User $user, ?array $alertUlids = null): int
    {
        $query = $user->alerts()->whereNull('read_at');

        // If specific alert ULIDs provided, filter to those
        if ($alertUlids !== null) {
            $query->whereIn('ulid', $alertUlids);
        }

        return $query->update(['read_at' => now()]);
    }
}
