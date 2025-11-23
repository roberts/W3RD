<?php

namespace App\Models\Games;

use App\Enums\GameTitle;
use App\GameEngine\ModeRegistry;
use App\GameTitles\BaseGameTitle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property GameTitle $title_slug
 * @property string $slug
 * @property string $name
 * @property bool $is_active
 * @property int|null $turn_time_limit_seconds
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Mode extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_slug',
        'slug',
        'name',
        'is_active',
    ];

    protected $casts = [
        'title_slug' => GameTitle::class,
        'is_active' => 'boolean',
    ];

    // Relationships
    /**
     * @return HasMany<Game>
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    // Helper methods

    /**
     * Get the handler class instance for this mode.
     * Requires a Game instance to determine the proper handler.
     *
     * @param  Game  $game  The game instance using this mode
     * @return BaseGameTitle The mode handler instance
     *
     * @throws \Exception if handler class is not registered
     */
    public function getHandler(Game $game): BaseGameTitle
    {
        return app(ModeRegistry::class)->resolve($game);
    }

    /**
     * Scope to get active modes only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by title slug.
     */
    public function scopeForTitle(Builder $query, string $titleSlug): Builder
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

    /**
     * Get seeded mode by title and slug.
     * Use this in tests after running ModeSeeder.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function seeded(string|GameTitle $titleSlug, string $slug = 'standard'): self
    {
        if ($titleSlug instanceof GameTitle) {
            $titleSlug = $titleSlug->value;
        }

        return self::where('title_slug', $titleSlug)
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Get seeded Connect Four mode.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function connectFour(string $slug = 'standard'): self
    {
        return self::seeded('connect-four', $slug);
    }

    /**
     * Get seeded Checkers mode.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function checkers(string $slug = 'standard'): self
    {
        return self::seeded('checkers', $slug);
    }

    /**
     * Get seeded Hearts mode.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function hearts(string $slug = 'standard'): self
    {
        return self::seeded('hearts', $slug);
    }
}
