<?php

namespace App\Models\Games;

use App\Enums\GameStatus;
use App\Enums\GameTitle;
use App\Enums\OutcomeType;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $ulid
 * @property Mode $mode
 * @property \Illuminate\Database\Eloquent\Collection<int, Player> $players
 * @property \Illuminate\Database\Eloquent\Collection<int, Action> $actions
 * @property Player|null $winner
 * @property GameStatus $status
 * @property int|null $mode_id
 * @property int|null $creator_id
 * @property int|null $winner_id
 * @property int|null $winner_position
 * @property int|null $turn_number
 * @property array|null $game_state
 * @property array|null $game_settings
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property OutcomeType|null $outcome_type
 * @property array|null $outcome_details
 * @property GameTitle $title_slug
 * @property string $game_title
 * @property int|null $max_players
 * @property \Illuminate\Support\Carbon|null $turn_ends_at
 * @property int|null $current_player_id
 * @property array|null $final_scores
 * @property array|null $xp_awarded
 * @property array|null $rewards
 */
class Game extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'ulid',
        'title_slug',
        'mode_id',
        'status',
        'creator_id',
        'winner_id',
        'winner_position',
        'outcome_type',
        'outcome_details',
        'turn_number',
        'game_state',
        'started_at',
        'completed_at',
        'expires_at',
        'player_count',
        'action_count',
        'duration_seconds',
        'max_players',
        'turn_ends_at',
        'current_player_id',
    ];

    protected $casts = [
        'title_slug' => GameTitle::class,
        'status' => GameStatus::class,
        'outcome_type' => OutcomeType::class,
        'outcome_details' => 'array',
        'game_state' => 'array',
        'final_scores' => 'array',
        'xp_awarded' => 'array',
        'rewards' => 'array',
        'turn_number' => 'integer',
        'mode_id' => 'integer',
        'winner_position' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'player_count' => 'integer',
        'action_count' => 'integer',
        'duration_seconds' => 'integer',
        'max_players' => 'integer',
        'turn_ends_at' => 'datetime',
        'current_player_id' => 'integer',
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
     * Scope to find a game by ULID with optional eager loading.
     *
     * @param array<int, string> $with
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
     * Scope to find games for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('players', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Get the player in this game for a specific user.
     */
    public function getPlayerForUser(int $userId): ?Player
    {
        /** @var Player|null */
        return $this->players()->where('user_id', $userId)->first();
    }

    /**
     * Scope to find completed games.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', GameStatus::COMPLETED);
    }

    /**
     * Scope to find active games.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', GameStatus::ACTIVE);
    }

    // Use ULID for route model binding
    public function getRouteKeyName()
    {
        return 'ulid';
    }

    // Relationships
    /**
     * @return BelongsTo<User, Game>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * @return BelongsTo<Mode, Game>
     */
    public function mode(): BelongsTo
    {
        return $this->belongsTo(Mode::class);
    }

    /**
     * @return HasMany<Player>
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /**
     * @return BelongsTo<Player, Game>
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'winner_id');
    }

    /**
     * @return HasMany<Action>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === GameStatus::COMPLETED;
    }

    public function isActive(): bool
    {
        return $this->status === GameStatus::ACTIVE;
    }

    public function getRecentActionTime(): \Illuminate\Support\Carbon
    {
        /** @var Action|null $lastAction */
        $lastAction = $this->actions()->latest()->first();

        return $lastAction ? $lastAction->created_at : ($this->started_at ?? $this->created_at);
    }

    /**
     * Get the player whose turn it is.
     */
    public function currentPlayer(): ?Player
    {
        $playerUlid = $this->game_state['current_player_ulid'] ?? null;

        if (! $playerUlid) {
            return null;
        }

        return $this->players->where('ulid', $playerUlid)->first();
    }
}
