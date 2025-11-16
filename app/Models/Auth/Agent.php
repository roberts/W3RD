<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'version',
        'difficulty',
        'configuration',
        'ai_logic_path',
        'strategy_type',
        'supported_game_titles',
        'available_hour_est',
        'error_count',
        'last_error_at',
        'debug_mode',
    ];

    protected $casts = [
        'difficulty' => 'integer',
        'configuration' => 'array',
        'supported_game_titles' => 'array',
        'available_hour_est' => 'integer',
        'error_count' => 'integer',
        'last_error_at' => 'datetime',
        'debug_mode' => 'boolean',
    ];

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        // Validate difficulty is between 1-10 before saving
        static::saving(function ($agent) {
            if ($agent->difficulty !== null && ($agent->difficulty < 1 || $agent->difficulty > 10)) {
                throw new \InvalidArgumentException('Difficulty must be between 1 and 10.');
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->hasOne(User::class);
    }

    // Helper methods
    public function isAvailableNow(): bool
    {
        if ($this->available_hour_est === null) {
            return true; // Available 24/7 if no restriction set
        }

        $currentHourEst = now('America/New_York')->hour;

        return $currentHourEst === $this->available_hour_est;
    }

    public function incrementErrorCount(): void
    {
        $this->increment('error_count');
        $this->update(['last_error_at' => now()]);
    }

    public function resetErrorCount(): void
    {
        $this->update([
            'error_count' => 0,
            'last_error_at' => null,
        ]);
    }

    public function getDifficultyLabel(): string
    {
        return match (true) {
            $this->difficulty <= 2 => 'Very Easy',
            $this->difficulty <= 4 => 'Easy',
            $this->difficulty <= 6 => 'Medium',
            $this->difficulty <= 8 => 'Hard',
            default => 'Expert',
        };
    }
}
