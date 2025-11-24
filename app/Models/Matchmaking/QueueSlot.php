<?php

namespace App\Models\Matchmaking;

use App\Matchmaking\Enums\QueueSlotStatus;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Builder;
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
        'matched_lobby_id',
        'preferences',
        'expires_at',
    ];

    protected $casts = [
        'status' => QueueSlotStatus::class,
        'preferences' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Scope to find a queue slot by ULID with optional eager loading.
     *
     * @param  array<int, string>  $with
     */
    public function scopeWithUlid(Builder $query, string $ulid, array $with = []): Builder
    {
        $query = $query->where('ulid', $ulid);

        if (! empty($with)) {
            $query->with($with);
        }

        return $query;
    }

    /**
     * Scope to find active queue slots.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', QueueSlotStatus::ACTIVE);
    }

    /**
     * Scope to find expired queue slots.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to find non-expired queue slots.
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

        /**
     * Get the user in this slot.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

        /**
     * Get the lobby associated with this slot (if any).
     *
     * @return BelongsTo<Lobby, $this>
     */
    public function lobby(): BelongsTo
    {
        return $this->belongsTo(Lobby::class, 'matched_lobby_id');
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
}
