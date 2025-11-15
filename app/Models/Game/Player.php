<?php

namespace App\Models\Game;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ulid',
        'game_id',
        'user_id',
        'name',
        'position_id',
        'color',
    ];

    protected $casts = [
        'position_id' => 'integer',
    ];

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
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actions()
    {
        return $this->hasMany(Action::class);
    }
}
