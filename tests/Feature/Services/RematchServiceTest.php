<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Player;
use App\Models\Game\RematchRequest;
use App\Services\RematchService;
use Carbon\Carbon;

describe('RematchService', function () {
    beforeEach(function () {
        $this->service = new RematchService;
    });

    describe('Create Rematch Request Validation', function () {
        test('throws exception for non-completed games', function () {
            $user = User::factory()->create();
            $game = Game::factory()->active()->create(['creator_id' => $user->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user->id]);

            expect(fn () => $this->service->createRematchRequest($game, $user))
                ->toThrow(\InvalidArgumentException::class, 'completed games');
        });

        test('throws exception when user was not a player', function () {
            $player = User::factory()->create();
            $nonPlayer = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $player->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $player->id]);

            expect(fn () => $this->service->createRematchRequest($game, $nonPlayer))
                ->toThrow(\InvalidArgumentException::class, 'not a player');
        });

        test('throws exception when opponent not found', function () {
            $user = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user->id]);

            expect(fn () => $this->service->createRematchRequest($game, $user))
                ->toThrow(\InvalidArgumentException::class, 'opponent');
        });

        test('throws exception when pending request already exists', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            // Create first request
            $this->service->createRematchRequest($game, $user1);

            // Try to create duplicate
            expect(fn () => $this->service->createRematchRequest($game, $user1))
                ->toThrow(\InvalidArgumentException::class, 'already exists');
        });
    });

    describe('Create Rematch Request Success', function () {
        test('creates rematch request with correct data', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            $request = $this->service->createRematchRequest($game, $user1);

            expect($request->original_game_id)->toBe($game->id)
                ->and($request->requesting_user_id)->toBe($user1->id)
                ->and($request->opponent_user_id)->toBe($user2->id)
                ->and($request->status)->toBe('pending');
        });

        test('sets expiration time based on config', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00'));

            $request = $this->service->createRematchRequest($game, $user1);

            expect($request->expires_at->format('Y-m-d H:i:s'))
                ->toBe('2024-01-01 12:05:00'); // 5 minutes default
        });
    });

    describe('Accept Rematch Request Validation', function () {
        test('throws exception when non-opponent tries to accept', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $user3 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            $request = $this->service->createRematchRequest($game, $user1);

            expect(fn () => $this->service->acceptRematchRequest($request, $user3))
                ->toThrow(\InvalidArgumentException::class, 'opponent');
        });

        test('throws exception when requester tries to accept own request', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            $request = $this->service->createRematchRequest($game, $user1);

            expect(fn () => $this->service->acceptRematchRequest($request, $user1))
                ->toThrow(\InvalidArgumentException::class, 'opponent');
        });

        test('throws exception when request not pending', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            $request = RematchRequest::create([
                'original_game_id' => $game->id,
                'requesting_user_id' => $user1->id,
                'opponent_user_id' => $user2->id,
                'status' => 'declined',
                'expires_at' => Carbon::now()->addMinutes(5),
            ]);

            expect(fn () => $this->service->acceptRematchRequest($request, $user2))
                ->toThrow(\InvalidArgumentException::class, 'no longer pending');
        });

        test('throws exception when request expired', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            $request = RematchRequest::create([
                'original_game_id' => $game->id,
                'requesting_user_id' => $user1->id,
                'opponent_user_id' => $user2->id,
                'status' => 'pending',
                'expires_at' => Carbon::now()->subMinutes(5), // Past expiration
            ]);

            expect(fn () => $this->service->acceptRematchRequest($request, $user2))
                ->toThrow(\InvalidArgumentException::class, 'expired');
        });
    });

    describe('Accept Rematch Request Success', function () {
        test('creates new game with same settings', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            $request = $this->service->createRematchRequest($game, $user1);
            $newGame = $this->service->acceptRematchRequest($request, $user2);

            expect($newGame->title_slug)->toBe($game->title_slug)
                ->and($newGame->mode_id)->toBe($game->mode_id)
                ->and($newGame->creator_id)->toBe($game->creator_id)
                ->and($newGame->status)->toBe(GameStatus::PENDING);
        });

        test('swaps player positions for fairness', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            $request = $this->service->createRematchRequest($game, $user1);
            $newGame = $this->service->acceptRematchRequest($request, $user2);

            $newPlayer1 = $newGame->players()->where('position_id', 1)->first();
            $newPlayer2 = $newGame->players()->where('position_id', 2)->first();

            expect($newPlayer1->user_id)->toBe($user2->id)
                ->and($newPlayer2->user_id)->toBe($user1->id);
        });

        test('updates request status to accepted', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            $request = $this->service->createRematchRequest($game, $user1);
            $newGame = $this->service->acceptRematchRequest($request, $user2);

            $request->refresh();
            expect($request->status)->toBe('accepted')
                ->and($request->new_game_id)->toBe($newGame->id);
        });
    });

    describe('Decline Rematch Request', function () {
        test('throws exception when non-opponent tries to decline', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $user3 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            $request = $this->service->createRematchRequest($game, $user1);

            expect(fn () => $this->service->declineRematchRequest($request, $user3))
                ->toThrow('Only the opponent');
        });

        test('throws exception when request not pending', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            $request = RematchRequest::create([
                'original_game_id' => $game->id,
                'requesting_user_id' => $user1->id,
                'opponent_user_id' => $user2->id,
                'status' => 'accepted',
                'expires_at' => Carbon::now()->addMinutes(5),
            ]);

            expect(fn () => $this->service->declineRematchRequest($request, $user2))
                ->toThrow('no longer pending');
        });

        test('updates status to declined', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            $request = $this->service->createRematchRequest($game, $user1);
            $declined = $this->service->declineRematchRequest($request, $user2);

            expect($declined->status)->toBe('declined');
        });
    });

    describe('Expire Old Requests', function () {
        test('expires requests past their expiration time', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            // Create expired request
            $expiredRequest = RematchRequest::create([
                'original_game_id' => $game->id,
                'requesting_user_id' => $user1->id,
                'opponent_user_id' => $user2->id,
                'status' => 'pending',
                'expires_at' => Carbon::now()->subMinutes(10),
            ]);

            $count = $this->service->expireOldRequests();

            expect($count)->toBe(1);
            $expiredRequest->refresh();
            expect($expiredRequest->status)->toBe('expired');
        });

        test('does not expire future requests', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            $futureRequest = $this->service->createRematchRequest($game, $user1);

            $count = $this->service->expireOldRequests();

            expect($count)->toBe(0);
            $futureRequest->refresh();
            expect($futureRequest->status)->toBe('pending');
        });

        test('only expires pending requests', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game->id, 'user_id' => $user2->id, 'position_id' => 2]);

            // Create expired but non-pending request
            RematchRequest::create([
                'original_game_id' => $game->id,
                'requesting_user_id' => $user1->id,
                'opponent_user_id' => $user2->id,
                'status' => 'accepted',
                'expires_at' => Carbon::now()->subMinutes(10),
            ]);

            $count = $this->service->expireOldRequests();

            expect($count)->toBe(0);
        });

        test('returns correct count of expired requests', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game1 = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            $game2 = Game::factory()->completed()->create(['creator_id' => $user1->id]);
            Player::factory()->create(['game_id' => $game1->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game1->id, 'user_id' => $user2->id, 'position_id' => 2]);
            Player::factory()->create(['game_id' => $game2->id, 'user_id' => $user1->id, 'position_id' => 1]);
            Player::factory()->create(['game_id' => $game2->id, 'user_id' => $user2->id, 'position_id' => 2]);

            // Create multiple expired requests
            RematchRequest::create([
                'original_game_id' => $game1->id,
                'requesting_user_id' => $user1->id,
                'opponent_user_id' => $user2->id,
                'status' => 'pending',
                'expires_at' => Carbon::now()->subMinutes(10),
            ]);

            RematchRequest::create([
                'original_game_id' => $game2->id,
                'requesting_user_id' => $user1->id,
                'opponent_user_id' => $user2->id,
                'status' => 'pending',
                'expires_at' => Carbon::now()->subMinutes(5),
            ]);

            $count = $this->service->expireOldRequests();

            expect($count)->toBe(2);
        });
    });
});
