<?php

namespace App\Models;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchmakingSignal extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ulid',
        'user_id',
        'game_preference',
        'skill_rating',
        'status',
        'preferences',
        'expires_at',
    ];

    protected $casts = [
        'preferences' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user who created this signal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if signal is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Check if signal has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Generate ULIDs for the explicit column instead of the primary key.
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }
}
