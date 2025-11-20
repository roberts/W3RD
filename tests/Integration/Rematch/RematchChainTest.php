<?php

use App\Enums\GameStatusEnum;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Lobby;
use App\Models\Game\Mode;
use App\Models\Game\Player;
use App\Services\RematchService;

describe('Rematch Chain Scenarios', function () {
    describe('Partial Rematch Acceptance', function () {
        it('creates new lobby with only accepting players', function () {
            $mode = Mode::factory()->create();

            $game = Game::factory()->withPlayers(4)->create([
                'mode_id' => $mode->id,
                'status' => GameStatusEnum::COMPLETED,
            ]);

            $users = $game->players->pluck('user');
            $players = $game->players;

            $rematchService = new RematchService;

            // Create rematch lobby
            $lobby = $rematchService->createRematchLobby($game);

            // Only 2 players join
            $lobby->players()->create(['user_id' => $users[0]->id, 'is_ready' => true]);
            $lobby->players()->create(['user_id' => $users[1]->id, 'is_ready' => true]);

            $lobby->refresh();

            // Should have 2 players, waiting for more
            expect($lobby->players)->toHaveCount(2)
                ->and($lobby->status)->toBe('pending');
        });
    });
});
