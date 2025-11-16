<?php

namespace App\Models\Game;

use App\Enums\GameStatus;
use App\Enums\GameTitle;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'turn_number',
        'game_state',
        'started_at',
        'finished_at',
        'expires_at',
        'player_count',
        'action_count',
        'duration_seconds',
    ];

    protected $casts = [
        'title_slug' => GameTitle::class,
        'status' => GameStatus::class,
        'game_state' => 'array',
        'turn_number' => 'integer',
        'mode_id' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'expires_at' => 'datetime',
        'player_count' => 'integer',
        'action_count' => 'integer',
        'duration_seconds' => 'integer',
    ];

    // Use ULID for route model binding
    public function getRouteKeyName()
    {
        return 'ulid';
    }

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function mode()
    {
        return $this->belongsTo(Mode::class);
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
        return $this->status === GameStatus::COMPLETED;
    }

    public function isActive(): bool
    {
        return $this->status === GameStatus::ACTIVE;
    }
}
