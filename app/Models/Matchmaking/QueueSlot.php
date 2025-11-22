<?php

namespace App\Models\Matchmaking;

use App\Matchmaking\Enums\QueueSlotStatus;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueSlot extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'queue_slots';

    protected $fillable = [
        'ulid',
        'user_id',
        'title_slug',
        'mode_id',
        'skill_rating',
        'status',
        'preferences',
        'expires_at',
    ];

    protected $casts = [
        'status' => QueueSlotStatus::class,
        'preferences' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user who occupies this queue slot.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if slot is active.
     */
    public function isActive(): bool
    {
        return $this->status === QueueSlotStatus::ACTIVE
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Check if slot has expired.
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
