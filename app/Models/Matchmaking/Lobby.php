<?php

namespace App\Models\Matchmaking;

use App\Enums\GameTitle;
use App\Matchmaking\Enums\LobbyPlayerStatus;
use App\Matchmaking\Enums\LobbyStatus;
use App\Models\Auth\User;
use App\Models\Games\Game;
use App\Models\Games\Mode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $ulid
 * @property GameTitle $game_title
 * @property GameTitle $title_slug
 * @property int|null $mode_id
 * @property int $host_id
 * @property LobbyStatus $status
 * @property bool $is_public
 * @property int $min_players
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LobbyPlayer> $players
 * @property-read User $host
 */
class Lobby extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ulid',
        'title_slug',
        'mode_id',
        'host_id',
        'game_id',
        'is_public',
        'min_players',
        'scheduled_at',
        'status',
    ];

    protected $casts = [
        'title_slug' => GameTitle::class,
        'is_public' => 'boolean',
        'min_players' => 'integer',
        'scheduled_at' => 'datetime',
        'status' => LobbyStatus::class,
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
     * Get the route key name for Laravel route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Scope to find a lobby by ULID with optional eager loading.
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
     * Scope to find lobbies with pending status.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', LobbyStatus::PENDING);
    }

    /**
     * Scope to find lobbies scheduled for a specific time.
     */
    public function scopeScheduledFor(Builder $query, mixed $dateTime): Builder
    {
        return $query->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $dateTime);
    }

    /**
     * Scope to find lobbies that are not scheduled (immediate start).
     */
    public function scopeNotScheduled(Builder $query): Builder
    {
        return $query->whereNull('scheduled_at');
    }

    /**
     * @return BelongsTo<Mode, Lobby>
     */
    /**
     * @return BelongsTo<Mode, $this>
     */
    public function mode(): BelongsTo
    {
        return $this->belongsTo(Mode::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    /**
     * @return HasMany<LobbyPlayer, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(LobbyPlayer::class);
    }

    /**
     * @return HasMany<LobbyPlayer, $this>
     */
    public function acceptedPlayers(): HasMany
    {
        return $this->players()->where('status', LobbyPlayerStatus::ACCEPTED);
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function isHost(User $user): bool
    {
        return $this->host_id === $user->id;
    }

    public function hasMinimumPlayers(): bool
    {
        return $this->acceptedPlayers()->count() >= $this->min_players;
    }

    public function canStartGame(): bool
    {
        $acceptedCount = $this->acceptedPlayers()->count();

        // If the game requires an exact player count, check for exact match
        if ($this->title_slug->requiresExactPlayerCount()) {
            return $acceptedCount === $this->title_slug->minPlayers();
        }

        // Otherwise, just check if we have minimum players
        return $acceptedCount >= $this->min_players;
    }

    public function isReady(): bool
    {
        return $this->status === LobbyStatus::READY;
    }

    public function isPending(): bool
    {
        return $this->status === LobbyStatus::PENDING;
    }

    public function markAsReady(): void
    {
        $this->update(['status' => LobbyStatus::READY]);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => LobbyStatus::COMPLETED]);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => LobbyStatus::CANCELLED]);
    }
}
