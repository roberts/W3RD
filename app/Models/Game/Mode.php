<?php

namespace App\Models\Game;

use App\Enums\GameTitle;
use App\Interfaces\GameTitleContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mode extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_slug',
        'slug',
        'name',
        'handler_class',
        'is_active',
    ];

    protected $casts = [
        'title_slug' => GameTitle::class,
        'is_active' => 'boolean',
    ];

    // Relationships
    public function games()
    {
        return $this->hasMany(Game::class);
    }

    // Helper methods
    
    /**
     * Get the handler class instance for this mode.
     *
     * @return GameTitleContract
     * @throws \Exception if handler class doesn't exist or doesn't implement GameTitleContract
     */
    public function getHandler(): GameTitleContract
    {
        if (!class_exists($this->handler_class)) {
            throw new \Exception("Handler class {$this->handler_class} does not exist");
        }

        $handler = new $this->handler_class();

        if (!$handler instanceof GameTitleContract) {
            throw new \Exception("Handler class {$this->handler_class} must implement GameTitleContract");
        }

        return $handler;
    }

    /**
     * Scope to get active modes only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by title slug.
     */
    public function scopeForTitle($query, string $titleSlug)
    {
        return $query->where('title_slug', $titleSlug);
    }

    /**
     * Get mode by title and mode slug.
     */
    public static function findByTitleAndSlug(string $titleSlug, string $modeSlug): ?self
    {
        return self::where('title_slug', $titleSlug)
            ->where('slug', $modeSlug)
            ->first();
    }
}
