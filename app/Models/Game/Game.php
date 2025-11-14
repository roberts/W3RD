<?php

namespace App\Models\Game;

use App\Enums\GameTitle;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ulid',
        'title_slug',
        'status',
        'created_by_user_id',
        'winner_id',
        'turn_number',
        'game_state',
    ];

    protected $casts = [
        'title_slug' => GameTitle::class,
        'game_state' => 'array',
        'turn_number' => 'integer',
    ];

    // Use ULID for route model binding
    public function getRouteKeyName()
    {
        return 'ulid';
    }

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function winner()
    {
        return $this->belongsTo(Player::class, 'winner_id');
    }

    public function actions()
    {
        return $this->hasMany(Action::class);
    }

    // Helper methods
    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
