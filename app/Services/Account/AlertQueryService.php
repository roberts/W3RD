<?php

namespace App\Services\Account;

use App\Models\Alert\Alert;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertQueryService
{
    /**
     * Build a query for user alerts with filters applied.
     *
     * @param  array<string, mixed>  $filters
     * @return HasMany<Alert>
     */
    public function buildUserAlertsQuery(User $user, array $filters): HasMany
    {
        $query = $user->alerts();

        // Apply read status filter
        if (isset($filters['read'])) {
            if ($filters['read']) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        // Apply type filter
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Apply date from filter
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        // Apply date to filter
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc');
    }
}
