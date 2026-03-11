<?php

use App\Agents\Scheduling\AgentSchedulingService;
use App\Models\Auth\Agent;
use App\Models\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    // Mock Redis for PlayerActivityService
    Redis::shouldReceive('setex')->andReturn(true)->byDefault();
    Redis::shouldReceive('get')->andReturn('idle')->byDefault();
    Redis::shouldReceive('expire')->andReturn(true)->byDefault();
    Redis::shouldReceive('del')->andReturn(true)->byDefault();
    Redis::shouldReceive('hmset')->andReturn(true)->byDefault();
    Redis::shouldReceive('hgetall')->andReturn([])->byDefault();
    Redis::shouldReceive('exists')->andReturn(false)->byDefault();
    Redis::shouldReceive('zadd')->andReturn(1)->byDefault();
    Redis::shouldReceive('zscore')->andReturn(null)->byDefault();
    Redis::shouldReceive('zrem')->andReturn(1)->byDefault();
});

describe('Agent Matchmaking', function () {
    it('matches human player with agent after timeout in queue', function () {
        // Create an available agent
        $agent = Agent::factory()->forGame('checkers')->alwaysAvailable()->create();
        $agentUser = User::factory()->create(['agent_id' => $agent->id]);

        // Create a human player
        $humanUser = User::factory()->create();

        // Simulate queue request for checkers
        // This would typically be an API call: POST /api/v1/matchmaking/queue
        // For this test, we're testing the underlying service logic

        // The actual queue service integration will be tested once
        // we integrate with the existing matchmaking system

        expect($agentUser->isAgent())->toBeTrue()
            ->and($humanUser->isAgent())->toBeFalse();
    });

    it('plays turn with human-like delay', function () {
        // This will be a more comprehensive test once CalculateAgentAction job is implemented
        // For now, we verify the basic structure

        $agent = Agent::factory()->create();
        $agentUser = User::factory()->create(['agent_id' => $agent->id]);

        expect($agentUser->isAgent())->toBeTrue()
            ->and($agent->difficulty)->toBeGreaterThanOrEqual(1)
            ->and($agent->difficulty)->toBeLessThanOrEqual(10);
    });

    it('respects game compatibility in matchmaking', function () {
        // Create agent that only plays hearts
        $heartsAgent = Agent::factory()->forGame('hearts')->create();
        $heartsUser = User::factory()->create(['agent_id' => $heartsAgent->id]);

        // Create agent that plays all games
        $allGamesAgent = Agent::factory()->allGames()->create();
        $allGamesUser = User::factory()->create(['agent_id' => $allGamesAgent->id]);

        expect($heartsAgent->supported_game_titles)->toBe(['hearts'])
            ->and($allGamesAgent->supported_game_titles)->toBe(['all']);
    });
});

describe('Recent Opponent Tracking', function () {
    it('prevents matching with same agent within 3 games', function () {
        // Create a human player
        $humanUser = User::factory()->create();

        // Create four different agents for checkers
        $agents = [];
        $agentUsers = [];
        for ($i = 0; $i < 4; $i++) {
            $agent = Agent::factory()->forGame('checkers')->alwaysAvailable()->create();
            $agentUser = User::factory()->create(['agent_id' => $agent->id]);
            $agents[] = $agent;
            $agentUsers[] = $agentUser;
        }

        // Mock recent opponents: agents 0, 1, 2 (last 3 games)
        Redis::shouldReceive('lrange')
            ->with("recent_opponents:{$humanUser->id}", 0, 2)
            ->andReturn([
                (string) $agentUsers[2]->id, // Most recent
                (string) $agentUsers[1]->id,
                (string) $agentUsers[0]->id, // 3rd most recent
            ]);

        // Ensure exists continues to work for cooldown checks
        Redis::makePartial();

        // Now find an agent for the next game
        $service = app(AgentSchedulingService::class);
        $foundAgent = $service->findAvailableAgent('checkers', null, $humanUser->id);

        // Should find agent 3 (the only one not in recent opponents)
        expect($foundAgent)->not->toBeNull()
            ->and($foundAgent->id)->toBe($agentUsers[3]->id)
            ->and($foundAgent->id)->not->toBe($agentUsers[0]->id)
            ->and($foundAgent->id)->not->toBe($agentUsers[1]->id)
            ->and($foundAgent->id)->not->toBe($agentUsers[2]->id);
    });

    it('allows rematching with agent after 3 other games', function () {
        // Create a human player
        $humanUser = User::factory()->create();

        // Create four agents
        $agents = [];
        $agentUsers = [];
        for ($i = 0; $i < 4; $i++) {
            $agent = Agent::factory()->forGame('checkers')->alwaysAvailable()->create();
            $agentUser = User::factory()->create(['agent_id' => $agent->id]);
            $agents[] = $agent;
            $agentUsers[] = $agentUser;
        }

        // Mock recent opponents: agents 3, 2, 1 (last 3 games)
        Redis::shouldReceive('lrange')
            ->with("recent_opponents:{$humanUser->id}", 0, 2)
            ->andReturn([
                (string) $agentUsers[3]->id, // Most recent
                (string) $agentUsers[2]->id,
                (string) $agentUsers[1]->id,
            ]);

        // Ensure exists continues to work for cooldown checks
        Redis::makePartial();

        // Now find an agent for the next game
        $service = app(AgentSchedulingService::class);
        $foundAgent = $service->findAvailableAgent('checkers', null, $humanUser->id);

        // Should be able to match with agent0 again (it's been 3+ games)
        expect($foundAgent)->not->toBeNull()
            ->and($foundAgent->id)->toBe($agentUsers[0]->id);
    });

    it('falls back to any agent if all available agents are recent opponents', function () {
        // Create a human player
        $humanUser = User::factory()->create();

        // Create only 2 agents (fewer than the recent opponent limit)
        $agent1 = Agent::factory()->forGame('checkers')->alwaysAvailable()->create();
        $agentUser1 = User::factory()->create(['agent_id' => $agent1->id]);

        $agent2 = Agent::factory()->forGame('checkers')->alwaysAvailable()->create();
        $agentUser2 = User::factory()->create(['agent_id' => $agent2->id]);

        // Mock that both are recent opponents
        Redis::shouldReceive('lrange')
            ->with("recent_opponents:{$humanUser->id}", 0, 2)
            ->andReturn([(string) $agentUser1->id, (string) $agentUser2->id]);

        // Ensure exists continues to work for cooldown checks
        Redis::makePartial();

        // Try to find an agent - should use lenient fallback
        $service = app(AgentSchedulingService::class);
        $foundAgent = $service->findAvailableAgent('checkers', null, $humanUser->id);

        // Should still find one of the agents (lenient fallback)
        expect($foundAgent)->not->toBeNull()
            ->and([$agentUser1->id, $agentUser2->id])->toContain($foundAgent->id);
    });
});
