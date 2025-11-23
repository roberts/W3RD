<?php

namespace App\Models\Games;

use App\Models\Access\Client;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Builder;
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
 * @property int $position
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

    /**
     * Scope to find a player by ULID with optional eager loading.
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
    /**
     * @return BelongsTo<Game, Player>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<User, Player>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Client, Player>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<Action>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }
}
