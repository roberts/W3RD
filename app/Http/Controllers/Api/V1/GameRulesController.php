<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

class GameRulesController extends Controller
{
    /**
     * Get the rules for a specific game title.
     *
     * @param  string  $gameTitle  The game title slug (e.g., 'validate-four')
     */
    public function show(string $gameTitle): JsonResponse
    {
        // Convert slug to StudlyCase for directory name (e.g., 'validate-four' → 'ValidateFour')
        $gameDirName = str_replace(' ', '', ucwords(str_replace('-', ' ', $gameTitle)));

        // Build the path to the base rules file
        $rulesPath = app_path("Games/{$gameDirName}/rules.php");

        if (! File::exists($rulesPath)) {
            return response()->json([
                'error' => 'Game not found',
                'message' => "No rules found for game '{$gameTitle}'",
            ], 404);
        }

        // Load the base rules
        $rules = require $rulesPath;

        // Add timeout details from the game mode if a mode is specified
        $mode = request()->query('mode');
        if ($mode) {
            $modeClass = $this->getModeClass($gameDirName, $mode);
            if ($modeClass) {
                $modeInstance = new $modeClass;
                $rules['timeout'] = [
                    'timelimit_seconds' => $modeInstance->getTimelimit(),
                    'grace_period_seconds' => 2,
                    'penalty' => $modeInstance->getTimeoutPenalty(),
                ];
            }
        }

        // Check for mode-specific rules and merge them
        if (isset($rules['modes']) && is_array($rules['modes'])) {
            foreach ($rules['modes'] as $modeName => $modeData) {
                $modeRulesPath = app_path("Games/{$gameDirName}/Modes/".ucfirst($modeName).'/rules.php');

                if (File::exists($modeRulesPath)) {
                    $modeRules = require $modeRulesPath;
                    // Merge mode-specific rules into base mode data
                    $rules['modes'][$modeName] = array_merge($modeData, $modeRules);
                }
            }
        }

        return response()->json($rules);
    }

    /**
     * Get the mode class for a specific game and mode.
     */
    protected function getModeClass(string $gameDirName, string $mode): ?string
    {
        // Convert mode name to class name (e.g., 'pop_out' → 'PopOutMode')
        $modeClassName = str_replace(' ', '', ucwords(str_replace('_', ' ', $mode))).'Mode';
        $modeClass = "App\\Games\\{$gameDirName}\\Modes\\{$modeClassName}";

        return class_exists($modeClass) ? $modeClass : null;
    }
}
