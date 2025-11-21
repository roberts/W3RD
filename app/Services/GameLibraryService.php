<?php

namespace App\Services;

use App\Enums\GameTitle;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class GameLibraryService
{
    /**
     * Get list of games with optional filtering.
     */
    public function getGames(?string $pacing = null, ?string $playerCount = null, ?string $category = null): array
    {
        $games = Cache::remember('game_library_all', 3600, function () {
            return collect(GameTitle::cases())->map(function (GameTitle $title) {
                return $this->formatGameSummary($title);
            })->toArray();
        });

        // Apply filters
        if ($pacing) {
            $games = array_filter($games, fn ($game) => $game['pacing'] === $pacing);
        }

        if ($playerCount) {
            $count = (int) $playerCount;
            $games = array_filter($games, fn ($game) => $count >= $game['min_players'] && $count <= $game['max_players']
            );
        }

        if ($category) {
            $games = array_filter($games, fn ($game) => in_array($category, $game['categories'] ?? [])
            );
        }

        return array_values($games);
    }

    /**
     * Get detailed metadata for a specific game.
     */
    public function getGameDetails(string $key): array
    {
        $title = $this->findGameTitle($key);

        return Cache::remember("game_library_details_{$key}", 3600, function () use ($title) {
            $details = $this->formatGameSummary($title);

            // Add extended details
            $details['modes'] = $this->getGameModes($title);
            $details['average_session_minutes'] = $this->getAverageSessionDuration($title);
            $details['thumbnail_url'] = $this->getThumbnailUrl($title);

            return $details;
        });
    }

    /**
     * Get entity definitions for a game (cards, units, boards).
     */
    public function getGameEntities(string $key): array
    {
        $title = $this->findGameTitle($key);
        $gameDirName = $this->getGameDirectoryName($key);

        return Cache::remember("game_entities_{$key}", 3600, function () use ($gameDirName) {
            $entitiesPath = app_path("Games/{$gameDirName}/entities.php");

            if (! File::exists($entitiesPath)) {
                return [];
            }

            return require $entitiesPath;
        });
    }

    /**
     * Format game summary data.
     */
    private function formatGameSummary(GameTitle $title): array
    {
        return [
            'key' => $title->value,
            'name' => $title->label(),
            'description' => $this->getDescription($title),
            'min_players' => $title->minPlayers(),
            'max_players' => $title->maxPlayers(),
            'pacing' => $this->getPacing($title),
            'complexity' => $this->getComplexity($title),
            'categories' => $this->getCategories($title),
        ];
    }

    /**
     * Find game title enum by key.
     */
    private function findGameTitle(string $key): GameTitle
    {
        $title = GameTitle::tryFrom($key);

        if (! $title) {
            throw new ResourceNotFoundException(
                "Game '{$key}' not found in library",
                'game',
                $key
            );
        }

        return $title;
    }

    /**
     * Get game description.
     */
    private function getDescription(GameTitle $title): string
    {
        return match ($title) {
            GameTitle::CONNECT_FOUR => 'Classic connect four game where players compete to align four pieces in a row.',
            GameTitle::CHECKERS => 'Classic board game where players move pieces diagonally, capturing opponent pieces by jumping over them.',
            GameTitle::HEARTS => 'Classic 4-player card game where the goal is to avoid taking hearts and the Queen of Spades, or shoot the moon to score big.',
            GameTitle::SPADES => 'Classic 4-player trick-taking card game played in partnerships.',
        };
    }

    /**
     * Get game pacing type.
     */
    private function getPacing(GameTitle $title): string
    {
        return match ($title) {
            GameTitle::CONNECT_FOUR => 'real-time',
            GameTitle::CHECKERS => 'turn-based',
            GameTitle::HEARTS => 'turn-based',
            GameTitle::SPADES => 'turn-based',
        };
    }

    /**
     * Get game complexity level.
     */
    private function getComplexity(GameTitle $title): string
    {
        return match ($title) {
            GameTitle::CONNECT_FOUR => 'simple',
            GameTitle::CHECKERS => 'moderate',
            GameTitle::HEARTS => 'moderate',
            GameTitle::SPADES => 'moderate',
        };
    }

    /**
     * Get game categories.
     */
    private function getCategories(GameTitle $title): array
    {
        return match ($title) {
            GameTitle::CONNECT_FOUR => ['strategy', 'casual'],
            GameTitle::CHECKERS => ['strategy', 'classic'],
            GameTitle::HEARTS => ['cards', 'classic'],
            GameTitle::SPADES => ['cards', 'classic'],
        };
    }

    /**
     * Get available game modes.
     */
    private function getGameModes(GameTitle $title): array
    {
        $gameDirName = $this->getGameDirectoryName($title->value);
        $modesPath = app_path("Games/{$gameDirName}/Modes");

        if (! File::isDirectory($modesPath)) {
            return [];
        }

        $modes = [];
        foreach (File::directories($modesPath) as $modeDir) {
            $modeName = strtolower(basename($modeDir));
            $modes[] = [
                'key' => $modeName,
                'name' => ucwords(str_replace('_', ' ', $modeName)),
            ];
        }

        return $modes;
    }

    /**
     * Get average session duration in minutes.
     */
    private function getAverageSessionDuration(GameTitle $title): int
    {
        return match ($title) {
            GameTitle::CONNECT_FOUR => 5,
            GameTitle::CHECKERS => 15,
            GameTitle::HEARTS => 20,
            GameTitle::SPADES => 25,
        };
    }

    /**
     * Get thumbnail URL for game.
     */
    private function getThumbnailUrl(GameTitle $title): string
    {
        return "/images/games/{$title->value}.jpg";
    }

    /**
     * Convert game key to directory name.
     */
    private function getGameDirectoryName(string $key): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $key)));
    }
}
