<?php

use App\Enums\GameStatus;
use App\GameEngine\Timers\TimerExpiredHandler;
use App\GameTitles\Hearts\Modes\StandardMode;
use App\Models\Games\Game;
use App\Models\Games\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('Timeout Handling Integration', function () {
    it('forfeits the game when a player times out in Standard Mode', function () {
        // Create game with expired turn
        $game = Game::factory()->create([
            'title_slug' => 'hearts',
            'status' => GameStatus::ACTIVE,
            'turn_ends_at' => Carbon::now()->subMinute(),
        ]);
        
        $players = collect();
        for ($i = 0; $i < 4; $i++) {
            $players->push(Player::factory()->for($game)->state(['position_id' => $i + 1])->create());
        }
        
        $timeoutPlayer = $players->first();
        
        // Setup handler
        $handler = new TimerExpiredHandler();
        $mode = new StandardMode($game);
        
        // Mock game state
        $gameState = new class {
            public ?string $currentPlayerUlid = null;
        };
        $gameState->currentPlayerUlid = $timeoutPlayer->ulid;
        
        // Execute
        $result = $handler->checkAndHandle($game, $mode, $gameState);
        
        // Assert
        expect($result->hasExpired)->toBeTrue()
            ->and($result->outcome->isFinished)->toBeTrue();
            
        // Check finding a winner (trait implementation picks first opponent)
        // Verify it isn't the timeout player
        expect($result->outcome->winnerUlid)->not->toBe($timeoutPlayer->ulid)
            ->and($result->outcome->winnerUlid)->not->toBeNull();
    });
});
