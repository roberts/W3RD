<?php

namespace App\Models\Matchmaking;

use App\Enums\GameTitle;
use App\Matchmaking\Enums\LobbyPlayerStatus;
use App\Matchmaking\Enums\LobbyStatus;
use App\Models\Auth\User;
use App\Models\Game\Game;
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
     * Scope to find a lobby by ULID with optional eager loading.
     */
    public function scopeWithUlid($query, string $ulid, array $with = [])
    {
        $query = $query->where('ulid', $ulid);

        if (! empty($with)) {
            $query->with($with);
        }

        return $query;
    }

    public function mode(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Game\Mode::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(LobbyPlayer::class);
    }

    public function acceptedPlayers(): HasMany
    {
        return $this->players()->where('status', LobbyPlayerStatus::ACCEPTED);
    }

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

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
