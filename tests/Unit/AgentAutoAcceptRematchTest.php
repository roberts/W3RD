<?php

use App\Enums\PlayerActivityState;
use App\GameEngine\Player\PlayerActivityManager;
use App\Jobs\AgentAutoAcceptRematch;
use App\Matchmaking\Events\ProposalAccepted;
use App\Matchmaking\Events\ProposalCancelled;
use App\Models\Auth\Agent;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Matchmaking\Proposal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

describe('AgentAutoAcceptRematch Job', function () {
    beforeEach(function () {
        Event::fake();

        $this->playerActivityManager = app(PlayerActivityManager::class);

        $this->states = [];
        $this->hashKeys = [];

        // Mock Redis to track state changes in memory
        Redis::shouldReceive('setex')
            ->with(\Mockery::pattern('/player:\d+:activity/'), 1800, \Mockery::any())
            ->andReturnUsing(function ($key, $ttl, $value) {
                $this->states[$key] = $value;

                return true;
            })
            ->byDefault();

        Redis::shouldReceive('get')
            ->with(\Mockery::pattern('/player:\d+:activity/'))
            ->andReturnUsing(function ($key) {
                return $this->states[$key] ?? 'idle'; // Default to idle for these tests
            })
            ->byDefault();

        Redis::shouldReceive('hmset')
            ->andReturnUsing(function ($key, $data) {
                $this->hashKeys[$key] = $data;

                return true;
            })
            ->byDefault();

        Redis::shouldReceive('hgetall')->andReturn([])->byDefault();

        Redis::shouldReceive('del')
            ->andReturnUsing(function ($key) {
                unset($this->hashKeys[$key]);

                return 1;
            })
            ->byDefault();

        Redis::shouldReceive('exists')
            ->andReturnUsing(function ($key) {
                return isset($this->hashKeys[$key]);
            })
            ->byDefault();

        Redis::shouldReceive('expire')->andReturn(true)->byDefault();

        $this->activityService = app(PlayerActivityManager::class);
    });

    describe('successful auto-accept', function () {
        it('accepts rematch when both players are IDLE', function () {
            $humanUser = User::factory()->create();
            $game = Game::factory()->completed()->withAgentOpponent($humanUser, 'alwaysAvailable')->create();
            $agentUser = $game->agent_user;

            $proposal = Proposal::factory()->create([
                'original_game_id' => $game->id,
                'requesting_user_id' => $humanUser->id,
                'opponent_user_id' => $agentUser->id,
                'status' => 'pending',
            ]);

            // Set both to IDLE
            $this->activityService->setState($humanUser->id, PlayerActivityState::IDLE);
            $this->activityService->setState($agentUser->id, PlayerActivityState::IDLE);

            // Set cooldown
            Redis::hmset("agent:{$agentUser->id}:cooldown", [
                'game_id' => $game->id,
                'human_user_id' => $humanUser->id,
            ]);

            $job = new AgentAutoAcceptRematch($proposal->ulid, $agentUser->id, $this->playerActivityManager);
            $job->handle();

            $proposal->refresh();
            expect($proposal->status)->toBe('accepted')
                ->and($proposal->game_id)->not->toBeNull();

            Event::assertDispatched(ProposalAccepted::class);
        });

        it('clears agent cooldown after accepting', function () {
            $humanUser = User::factory()->create();
            $game = Game::factory()->completed()->withAgentOpponent($humanUser, 'alwaysAvailable')->create();
            $agentUser = $game->agent_user;

            $proposal = Proposal::factory()->create([
                'original_game_id' => $game->id,
                'requesting_user_id' => $humanUser->id,
                'opponent_user_id' => $agentUser->id,
                'status' => 'pending',
            ]);

            $this->activityService->setState($humanUser->id, PlayerActivityState::IDLE);
            $this->activityService->setState($agentUser->id, PlayerActivityState::IDLE);

            $cooldownKey = "agent:{$agentUser->id}:cooldown";
            Redis::hmset($cooldownKey, [
                'game_id' => $game->id,
                'human_user_id' => $humanUser->id,
            ]);

            expect(Redis::exists($cooldownKey))->toBeTrue();

            $job = new AgentAutoAcceptRematch($proposal->ulid, $agentUser->id, $this->playerActivityManager);
            $job->handle();

            expect(Redis::exists($cooldownKey))->toBeFalse();
        });
    });

    describe('validation checks', function () {
        it('does nothing if rematch request not found', function () {
            $job = new AgentAutoAcceptRematch('non-existent-ulid', 999, $this->playerActivityManager);
            $job->handle();

            Event::assertNotDispatched(ProposalAccepted::class);
            Event::assertNotDispatched(ProposalCancelled::class);
        });

        it('skips if rematch no longer pending', function () {
            $agent = Agent::factory()->alwaysAvailable()->create();
            $agentUser = User::factory()->create(['agent_id' => $agent->id]);
            $humanUser = User::factory()->create();

            $game = Game::factory()->completed()->create();

            $proposal = Proposal::factory()->declined()->create([
                'original_game_id' => $game->id,
                'requesting_user_id' => $humanUser->id,
                'opponent_user_id' => $agentUser->id,
            ]);

            $job = new AgentAutoAcceptRematch($proposal->ulid, $agentUser->id, $this->playerActivityManager);
            $job->handle();

            expect($proposal->fresh()->status)->toBe('declined');
            Event::assertNotDispatched(ProposalAccepted::class);
        });

        it('cancels if agent not IDLE', function () {
            $humanUser = User::factory()->create();
            $game = Game::factory()->completed()->withAgentOpponent($humanUser, 'alwaysAvailable')->create();
            $agentUser = $game->agent_user;

            $proposal = Proposal::factory()->create([
                'original_game_id' => $game->id,
                'requesting_user_id' => $humanUser->id,
                'opponent_user_id' => $agentUser->id,
                'status' => 'pending',
            ]);

            $this->activityService->setState($humanUser->id, PlayerActivityState::IDLE);
            $this->activityService->setState($agentUser->id, PlayerActivityState::IN_GAME);

            $job = new AgentAutoAcceptRematch($proposal->ulid, $agentUser->id, $this->playerActivityManager);
            $job->handle();

            $proposal->refresh();
            expect($proposal->status)->toBe('cancelled');

            Event::assertDispatched(ProposalCancelled::class, function ($event) use ($proposal) {
                return $event->proposal->id === $proposal->id
                    && $event->reason === 'opponent_unavailable';
            });
        });

        it('cancels if requester not IDLE', function () {
            $humanUser = User::factory()->create();
            $game = Game::factory()->completed()->withAgentOpponent($humanUser, 'alwaysAvailable')->create();
            $agentUser = $game->agent_user;

            $proposal = Proposal::factory()->create([
                'original_game_id' => $game->id,
                'requesting_user_id' => $humanUser->id,
                'opponent_user_id' => $agentUser->id,
                'status' => 'pending',
            ]);

            $this->activityService->setState($humanUser->id, PlayerActivityState::IN_QUEUE);
            $this->activityService->setState($agentUser->id, PlayerActivityState::IDLE);

            $job = new AgentAutoAcceptRematch($proposal->ulid, $agentUser->id, $this->playerActivityManager);
            $job->handle();

            $proposal->refresh();
            expect($proposal->status)->toBe('cancelled');

            Event::assertDispatched(ProposalCancelled::class, function ($event) use ($proposal) {
                return $event->proposal->id === $proposal->id
                    && $event->reason === 'requester_unavailable';
            });
        });

        it('cancels if both players not IDLE', function () {
            $humanUser = User::factory()->create();
            $game = Game::factory()->completed()->withAgentOpponent($humanUser, 'alwaysAvailable')->create();
            $agentUser = $game->agent_user;

            $proposal = Proposal::factory()->create([
                'original_game_id' => $game->id,
                'requesting_user_id' => $humanUser->id,
                'opponent_user_id' => $agentUser->id,
                'status' => 'pending',
            ]);

            $this->activityService->setState($humanUser->id, PlayerActivityState::IN_LOBBY);
            $this->activityService->setState($agentUser->id, PlayerActivityState::IN_QUEUE);

            $job = new AgentAutoAcceptRematch($proposal->ulid, $agentUser->id, $this->playerActivityManager);
            $job->handle();

            $proposal->refresh();
            expect($proposal->status)->toBe('cancelled');
        });
    });

    describe('edge cases', function () {
        it('handles missing agent user gracefully', function () {
            $humanUser = User::factory()->create();
            $fakeAgentUser = User::factory()->create(); // Create but will be deleted

            $game = Game::factory()->completed()->create();

            $proposal = Proposal::factory()->create([
                'original_game_id' => $game->id,
                'requesting_user_id' => $humanUser->id,
                'opponent_user_id' => $fakeAgentUser->id,
                'status' => 'pending',
            ]);

            $fakeAgentId = $fakeAgentUser->id;
            $fakeAgentUser->delete(); // Delete to simulate missing user

            $job = new AgentAutoAcceptRematch($proposal->ulid, $fakeAgentId, $this->playerActivityManager);

            // Should not throw exception
            $job->handle();

            // Should cancel since agent user not found
            expect($proposal->fresh()->status)->toBe('cancelled');
            Event::assertDispatched(ProposalCancelled::class);
        });

        it('handles race condition where rematch accepted by another process', function () {
            $humanUser = User::factory()->create();
            $game = Game::factory()->completed()->withAgentOpponent($humanUser, 'alwaysAvailable')->create();
            $agentUser = $game->agent_user;

            $proposal = Proposal::factory()->accepted()->create([
                'original_game_id' => $game->id,
                'requesting_user_id' => $humanUser->id,
                'opponent_user_id' => $agentUser->id,
            ]);

            $this->activityService->setState($humanUser->id, PlayerActivityState::IDLE);
            $this->activityService->setState($agentUser->id, PlayerActivityState::IDLE);

            $job = new AgentAutoAcceptRematch($proposal->ulid, $agentUser->id, $this->playerActivityManager);
            $job->handle();

            // Should skip without error
            expect($proposal->fresh()->status)->toBe('accepted');
        });
    });
});
