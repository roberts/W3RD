<?php

namespace App\Models\Game;

use App\Models\Access\Client;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $ulid
 * @property int $game_id
 * @property int $user_id
 * @property int $client_id
 * @property string|null $name
 * @property int|null $position_id
 * @property string|null $color
 * @property User $user
 * @property Client $client
 * @property Game $game
 */
class Player extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ulid',
        'game_id',
        'user_id',
        'client_id',
        'name',
        'position_id',
        'color',
    ];

    protected $casts = [
        'position_id' => 'integer',
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

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        // Validate position_id is between 1-10 before saving
        static::saving(function ($player) {
            if ($player->position_id !== null && ($player->position_id < 1 || $player->position_id > 10)) {
                throw new \InvalidArgumentException('Position ID must be between 1 and 10.');
            }
        });
    }

    // Use ULID for route model binding
    public function getRouteKeyName()
    {
        return 'ulid';
    }

    // Relationships
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }
}
