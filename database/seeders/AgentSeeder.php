<?php

namespace Database\Seeders;

use App\Models\Auth\Agent;
use App\Models\Auth\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding agents...');

        // Create a variety of agents with different difficulty levels and strategies

        // Easy agents - Random strategy
        $this->createAgent('Rookie Randy', 'RandomLogic', 2, ['checkers', 'validatefour'], 14);
        $this->createAgent('Casual Carl', 'RandomLogic', 3, 'all', null);

        // Medium agents - Heuristic strategy
        $this->createAgent('Strategic Sarah', 'HeuristicLogic', 5, ['checkers', 'hearts'], 18);
        $this->createAgent('Balanced Bob', 'HeuristicLogic', 6, 'all', 20);
        $this->createAgent('Tactical Tina', 'HeuristicLogic', 6, ['validatefour'], 15);

        // Hard agents - Minimax strategy
        $this->createAgent('Calculating Chris', 'MinimaxLogic', 7, ['checkers'], 10);
        $this->createAgent('Master Maya', 'MinimaxLogic', 8, ['hearts', 'validatefour'], 22);

        // Expert agents - Deep Minimax
        $this->createAgent('Grandmaster Gary', 'MinimaxLogic', 9, 'all', null);
        $this->createAgent('AI Alexandra', 'MinimaxLogic', 10, ['checkers'], 16);

        // Specialist agents with mode-specific configurations
        $blitzAgent = $this->createAgent('Blitz Betty', 'HeuristicLogic', 5, ['hearts'], 19);
        $blitzAgent->configuration = [
            'hearts' => ['blitz_difficulty' => 8],
        ];
        $blitzAgent->save();

        $this->command->info('Agents seeded successfully!');
    }

    /**
     * Create an agent with a corresponding user.
     *
     * @param string $name
     * @param string $logicClass Short class name (e.g., 'RandomLogic')
     * @param int $difficulty
     * @param array|string $supportedGames
     * @param int|null $availableHour
     * @return Agent
     */
    protected function createAgent(
        string $name,
        string $logicClass,
        int $difficulty,
        array|string $supportedGames,
        ?int $availableHour
    ): Agent {
        // Create the agent profile
        $agent = Agent::create([
            'name' => $name,
            'description' => "AI agent using {$logicClass} strategy at difficulty {$difficulty}",
            'version' => '1.0.0',
            'difficulty' => $difficulty,
            'ai_logic_path' => "App\\Agents\\Logic\\{$logicClass}",
            'strategy_type' => $this->getStrategyType($logicClass),
            'supported_game_titles' => $supportedGames,
            'available_hour_est' => $availableHour,
            'configuration' => null,
            'error_count' => 0,
            'last_error_at' => null,
            'debug_mode' => false,
        ]);

        // Create corresponding user
        $username = strtolower(str_replace(' ', '_', $name));
        $email = $username . '@agents.protocol.game';

        $user = User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make(bin2hex(random_bytes(32))), // Random unusable password
            'agent_id' => $agent->id,
            'email_verified_at' => now(),
            'registration_client_id' => 1, // Assuming client ID 1 exists
        ]);

        $this->command->info("Created agent: {$name} (ID: {$agent->id}, User ID: {$user->id})");

        return $agent;
    }

    /**
     * Get strategy type based on logic class name.
     *
     * @param string $logicClass
     * @return string
     */
    protected function getStrategyType(string $logicClass): string
    {
        return match ($logicClass) {
            'RandomLogic' => 'random',
            'MinimaxLogic' => 'balanced',
            'HeuristicLogic' => 'balanced',
            default => 'balanced',
        };
    }
}
