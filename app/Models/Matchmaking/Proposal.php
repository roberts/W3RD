<?php

namespace App\Models\Matchmaking;

use App\Matchmaking\Enums\ProposalStatus;
use App\Matchmaking\Enums\ProposalType;
use App\Models\Auth\User;
use App\Models\Games\Game;
use App\Models\Games\Mode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $ulid
 * @property int $requesting_user_id
 * @property int $opponent_user_id
 * @property string $title_slug
 * @property int|null $mode_id
 * @property ProposalType $type
 * @property int|null $original_game_id
 * @property int|null $game_id
 * @property array<string, mixed>|null $game_settings
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property ProposalStatus $status
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Proposal extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ulid',
        'requesting_user_id',
        'opponent_user_id',
        'title_slug',
        'mode_id',
        'type',
        'original_game_id',
        'game_id',
        'game_settings',
        'responded_at',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'type' => ProposalType::class,
        'status' => ProposalStatus::class,
        'game_settings' => 'array',
        'responded_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Ensure ULIDs are generated for the "ulid" column instead of the primary key.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Scope to find a proposal by ULID with optional eager loading.
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
     * Scope to find proposals with pending status.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ProposalStatus::PENDING);
    }

    /**
     * Scope to find expired proposals.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to find non-expired proposals.
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * @return BelongsTo<User, Proposal>
     */
    public function requestingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requesting_user_id');
    }

    /**
     * @return BelongsTo<User, Proposal>
     */
    public function opponentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opponent_user_id');
    }

    /**
     * @return BelongsTo<Game, Proposal>
     */
    public function originalGame(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'original_game_id');
    }

    /**
     * @return BelongsTo<Game, Proposal>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<Mode, Proposal>
     */
    public function mode(): BelongsTo
    {
        return $this->belongsTo(Mode::class);
    }

    public function isPending(): bool
    {
        return $this->status === ProposalStatus::PENDING && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast() && $this->status === ProposalStatus::PENDING;
    }

    public function accept(): void
    {
        $this->status = ProposalStatus::ACCEPTED;
        $this->responded_at = now();
        $this->save();
    }

    public function decline(): void
    {
        $this->status = ProposalStatus::DECLINED;
        $this->responded_at = now();
        $this->save();
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
