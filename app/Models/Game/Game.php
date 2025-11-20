<?php

namespace App\Models\Game;

use App\Enums\GameStatus;
use App\Enums\GameTitle;
use App\Enums\OutcomeType;
use App\Models\Auth\User;
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
 * @property GameStatus $status
 * @property int|null $mode_id
 * @property int|null $creator_id
 * @property int|null $winner_id
 * @property int|null $winner_position
 * @property int|null $turn_number
 * @property array|null $game_state
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property OutcomeType|null $outcome_type
 * @property array|null $outcome_details
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
    ];

    protected $casts = [
        'title_slug' => GameTitle::class,
        'status' => GameStatus::class,
        'outcome_type' => OutcomeType::class,
        'outcome_details' => 'array',
        'game_state' => 'array',
        'turn_number' => 'integer',
        'mode_id' => 'integer',
        'winner_position' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'player_count' => 'integer',
        'action_count' => 'integer',
        'duration_seconds' => 'integer',
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

    // Use ULID for route model binding
    public function getRouteKeyName()
    {
        return 'ulid';
    }

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function mode(): BelongsTo
    {
        return $this->belongsTo(Mode::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'winner_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }

    // Helper methods
    public function isFinished(): bool
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
}
