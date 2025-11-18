<?php

namespace App\Models\Game;

use App\Enums\ActionType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $ulid
 * @property int $game_id
 * @property int $player_id
 * @property int $turn_number
 * @property ActionType $action_type
 * @property array|null $action_details
 * @property string|null $status
 * @property Player $player
 * @property Game $game
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Action extends Model
{
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

    // Relationships
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
