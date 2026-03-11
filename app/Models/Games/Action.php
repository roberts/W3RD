<?php

namespace App\Models\Games;

use App\Enums\ActionType;
use Database\Factories\Games\ActionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $ulid
 * @property int $game_id
 * @property int $player_id
 * @property int $turn_number
 * @property ActionType $action_type
 * @property array<string, mixed>|null $action_details
 * @property string|null $status
 * @property array<string, mixed>|null $resulting_state
 * @property Player $player
 * @property Game $game
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Action extends Model
{
    /** @use HasFactory<ActionFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'game_id',
        'player_id',
        'turn_number',
        'action_type',
        'action_details',
        'status',
        'error_code',
        'timestamp_client',
        'coordination_group',
        'coordination_sequence',
        'is_coordinated',
        'coordination_completed_at',
    ];

    protected $casts = [
        'action_type' => ActionType::class,
        'action_details' => 'array',
        'turn_number' => 'integer',
        'timestamp_client' => 'datetime',
        'is_coordinated' => 'boolean',
        'coordination_sequence' => 'integer',
        'coordination_completed_at' => 'datetime',
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
     * Scope to find an action by ULID with optional eager loading.
     *
     * @param  Builder<Action>  $query
     * @param  array<int, string>  $with
     * @return Builder<Action>
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
     * Scope to find actions with a specific coordination group.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithCoordinationGroup(Builder $query, string $coordinationGroup): Builder
    {
        return $query->where('coordination_group', $coordinationGroup);
    }

    /**
     * Scope to find actions pending coordination completion.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePendingCoordination(Builder $query): Builder
    {
        return $query->where('is_coordinated', true)
            ->whereNull('coordination_completed_at');
    }

    /**
     * Get the route key name for Laravel route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    // Relationships
    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
