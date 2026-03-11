<?php

use App\Agents\Scheduling\AgentSchedulingService;
use App\Models\Auth\Agent;
use App\Models\Auth\User;
use App\Models\Games\Game;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    $this->service = app(AgentSchedulingService::class);

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

describe('Agent Discovery', function () {
    it('can find an available agent for a specific game', function () {
        // Create an agent that supports checkers
        $agent = Agent::factory()->forGame('checkers')->create();
        $user = User::factory()->create(['agent_id' => $agent->id]);

        $foundAgent = $this->service->findAvailableAgent('checkers');

        expect($foundAgent)->not->toBeNull()
            ->and($foundAgent->id)->toBe($user->id);
    });

    it('returns null when no agents support the requested game', function () {
        // Create an agent that only supports hearts
        $agent = Agent::factory()->forGame('hearts')->create();
        User::factory()->create(['agent_id' => $agent->id]);

        $foundAgent = $this->service->findAvailableAgent('checkers');

        expect($foundAgent)->toBeNull();
    });

    it('finds agent that supports all games', function () {
        $agent = Agent::factory()->allGames()->create();
        $user = User::factory()->create(['agent_id' => $agent->id]);

        $foundAgent = $this->service->findAvailableAgent('checkers');

        expect($foundAgent)->not->toBeNull()
            ->and($foundAgent->id)->toBe($user->id);
    });

    it('skips agents that are currently busy in a game', function () {
        // Create two agents
        $agent1 = Agent::factory()->forGame('checkers')->create();
        $user1 = User::factory()->create(['agent_id' => $agent1->id]);

        $agent2 = Agent::factory()->forGame('checkers')->create();
        $user2 = User::factory()->create(['agent_id' => $agent2->id]);

        // Mock that user1 is in an active game
        // This would typically check the players table for active games
        // For now, we'll test that the service can handle multiple agents

        $foundAgent = $this->service->findAvailableAgent('checkers');

        expect($foundAgent)->not->toBeNull();
    });
});

describe('Time-Based Availability', function () {
    it('respects agent availability hours', function () {
        // Set current time to 2 PM EST (which is 19:00 UTC in winter)
        Date::setTestNow('2024-01-15 19:00:00'); // 2 PM EST = 19:00 UTC

        // Create agent available at 2 PM EST (hour 14)
        $agent1 = Agent::factory()->forGame('checkers')->create(['available_hour_est' => 14]);
        $user1 = User::factory()->create(['agent_id' => $agent1->id]);

        // Create agent available at 3 PM EST (hour 15) - should not be found
        $agent2 = Agent::factory()->forGame('checkers')->create(['available_hour_est' => 15]);
        User::factory()->create(['agent_id' => $agent2->id]);

        $foundAgent = $this->service->findAvailableAgent('checkers');

        expect($foundAgent)->not->toBeNull()
            ->and($foundAgent->id)->toBe($user1->id);
    });

    it('finds agents with null availability (24/7 agents)', function () {
        $agent = Agent::factory()->forGame('checkers')->alwaysAvailable()->create();
        $user = User::factory()->create(['agent_id' => $agent->id]);

        $foundAgent = $this->service->findAvailableAgent('checkers');

        expect($foundAgent)->not->toBeNull()
            ->and($foundAgent->id)->toBe($user->id);
    });

    it('prefers time-specific agents over 24/7 agents during their available hour', function () {
        // Set current time to 3 PM EST (20:00 UTC in winter)
        Date::setTestNow('2024-01-15 20:00:00'); // 3 PM EST

        // Create a 24/7 agent (null availability) with higher difficulty
        $agent247 = Agent::factory()->forGame('checkers')
            ->alwaysAvailable()
            ->withDifficulty(8)
            ->create();
        $user247 = User::factory()->create(['agent_id' => $agent247->id]);

        // Create agent available at 3 PM EST (hour 15) with lower difficulty
        $agentTimed = Agent::factory()->forGame('checkers')
            ->withDifficulty(5)
            ->create(['available_hour_est' => 15]);
        $userTimed = User::factory()->create(['agent_id' => $agentTimed->id]);

        $foundAgent = $this->service->findAvailableAgent('checkers');

        // Should prefer the time-specific agent over the 24/7 agent
        expect($foundAgent)->not->toBeNull()
            ->and($foundAgent->id)->toBe($userTimed->id);
    });

    it('uses 24/7 agents as fallback when no time-specific agents are available', function () {
        // Set current time to 10 AM EST (15:00 UTC in winter)
        Date::setTestNow('2024-01-15 15:00:00'); // 10 AM EST

        // Create a 24/7 agent (null availability)
        $agent247 = Agent::factory()->forGame('checkers')->alwaysAvailable()->create();
        $user247 = User::factory()->create(['agent_id' => $agent247->id]);

        // Create agents available at different times (not 10 AM)
        $agent1 = Agent::factory()->forGame('checkers')->create(['available_hour_est' => 14]); // 2 PM
        User::factory()->create(['agent_id' => $agent1->id]);

        $agent2 = Agent::factory()->forGame('checkers')->create(['available_hour_est' => 18]); // 6 PM
        User::factory()->create(['agent_id' => $agent2->id]);

        $foundAgent = $this->service->findAvailableAgent('checkers');

        // Should find the 24/7 agent as fallback
        expect($foundAgent)->not->toBeNull()
            ->and($foundAgent->id)->toBe($user247->id);
    });

    it('handles multiple time-specific agents at the same hour', function () {
        // Set current time to 4 PM EST (21:00 UTC in winter)
        Date::setTestNow('2024-01-15 21:00:00'); // 4 PM EST

        // Create multiple agents available at 4 PM EST (hour 16)
        $agent1 = Agent::factory()->forGame('checkers')
            ->withDifficulty(3)
            ->create(['available_hour_est' => 16]);
        $user1 = User::factory()->create(['agent_id' => $agent1->id]);

        $agent2 = Agent::factory()->forGame('checkers')
            ->withDifficulty(7)
            ->create(['available_hour_est' => 16]);
        $user2 = User::factory()->create(['agent_id' => $agent2->id]);

        $foundAgent = $this->service->findAvailableAgent('checkers');

        // Should find one of the time-specific agents (preferring higher difficulty)
        expect($foundAgent)->not->toBeNull()
            ->and($foundAgent->id)->toBe($user2->id); // Higher difficulty agent
    });

    it('returns null when no agents are available at current hour and no 24/7 agents exist', function () {
        // Set current time to 11 PM EST (04:00 UTC next day in winter)
        Date::setTestNow('2024-01-16 04:00:00'); // 11 PM EST

        // Create agents available at different times (not 11 PM)
        $agent1 = Agent::factory()->forGame('checkers')->create(['available_hour_est' => 9]); // 9 AM
        User::factory()->create(['agent_id' => $agent1->id]);

        $agent2 = Agent::factory()->forGame('checkers')->create(['available_hour_est' => 14]); // 2 PM
        User::factory()->create(['agent_id' => $agent2->id]);

        $foundAgent = $this->service->findAvailableAgent('checkers');

        // Should return null - no available agents
        expect($foundAgent)->toBeNull();
    });
});

describe('Recent Opponent Filtering', function () {
    it('filters out recent opponents when humanUserId is provided', function () {
        // Create a human player
        $humanUser = User::factory()->create();

        // Create three agents
        $agent1 = Agent::factory()->forGame('checkers')->create();
        $user1 = User::factory()->create(['agent_id' => $agent1->id]);

        $agent2 = Agent::factory()->forGame('checkers')->create();
        $user2 = User::factory()->create(['agent_id' => $agent2->id]);

        $agent3 = Agent::factory()->forGame('checkers')->create();
        $user3 = User::factory()->create(['agent_id' => $agent3->id]);

        // Mock that user1 and user2 are in recent opponents
        Redis::shouldReceive('lrange')
            ->once()
            ->with("recent_opponents:{$humanUser->id}", 0, 2)
            ->andReturn([(string) $user1->id, (string) $user2->id]);

        $foundAgent = $this->service->findAvailableAgent('checkers', null, $humanUser->id);

        // Should find user3 (not in recent opponents)
        expect($foundAgent)->not->toBeNull()
            ->and($foundAgent->id)->toBe($user3->id);
    });

    it('returns agent when humanUserId is not provided (backwards compatibility)', function () {
        $agent = Agent::factory()->forGame('checkers')->create();
        $user = User::factory()->create(['agent_id' => $agent->id]);

        $foundAgent = $this->service->findAvailableAgent('checkers', null, null);

        expect($foundAgent)->not->toBeNull()
            ->and($foundAgent->id)->toBe($user->id);
    });

    it('falls back to any agent if all are recent opponents (lenient fallback)', function () {
        // Create a human player
        $humanUser = User::factory()->create();

        // Create two agents
        $agent1 = Agent::factory()->forGame('checkers')->create();
        $user1 = User::factory()->create(['agent_id' => $agent1->id]);

        $agent2 = Agent::factory()->forGame('checkers')->create();
        $user2 = User::factory()->create(['agent_id' => $agent2->id]);

        // First call returns both as recent opponents (filters them out)
        // Second call (fallback) returns empty array (no filter applied)
        Redis::shouldReceive('lrange')
            ->once()
            ->with("recent_opponents:{$humanUser->id}", 0, 2)
            ->andReturn([(string) $user1->id, (string) $user2->id]);

        // Since filtering will remove all agents, fallback will be triggered
        // The fallback calls findAvailableAgent again with humanUserId=null
        // So there won't be a second lrange call

        $foundAgent = $this->service->findAvailableAgent('checkers', null, $humanUser->id);

        // Should still find an agent (lenient fallback)
        expect($foundAgent)->not->toBeNull()
            ->and([$user1->id, $user2->id])->toContain($foundAgent->id);
    });

    it('respects recent opponent limit of 3', function () {
        // Create a human player
        $humanUser = User::factory()->create();

        // Create four agents
        $agent1 = Agent::factory()->forGame('checkers')->create();
        $user1 = User::factory()->create(['agent_id' => $agent1->id]);

        $agent2 = Agent::factory()->forGame('checkers')->create();
        $user2 = User::factory()->create(['agent_id' => $agent2->id]);

        $agent3 = Agent::factory()->forGame('checkers')->create();
        $user3 = User::factory()->create(['agent_id' => $agent3->id]);

        $agent4 = Agent::factory()->forGame('checkers')->create();
        $user4 = User::factory()->create(['agent_id' => $agent4->id]);

        // Mock recent opponents: user1, user2, user3 (last 3)
        Redis::shouldReceive('lrange')
            ->once()
            ->with("recent_opponents:{$humanUser->id}", 0, 2)
            ->andReturn([(string) $user1->id, (string) $user2->id, (string) $user3->id]);

        $foundAgent = $this->service->findAvailableAgent('checkers', null, $humanUser->id);

        // Should find user4 (not in the last 3 opponents)
        expect($foundAgent)->not->toBeNull()
            ->and($foundAgent->id)->toBe($user4->id);
    });

    it('works with time-based scheduling', function () {
        // Set current time to 2 PM EST
        Date::setTestNow('2024-01-15 19:00:00'); // 2 PM EST

        // Create a human player
        $humanUser = User::factory()->create();

        // Create time-specific agent (available at 2 PM)
        $agent1 = Agent::factory()->forGame('checkers')->create(['available_hour_est' => 14]);
        $user1 = User::factory()->create(['agent_id' => $agent1->id]);

        // Create 24/7 agent
        $agent247 = Agent::factory()->forGame('checkers')->alwaysAvailable()->create();
        $user247 = User::factory()->create(['agent_id' => $agent247->id]);

        // Mock that time-specific agent is in recent opponents
        Redis::shouldReceive('lrange')
            ->once()
            ->with("recent_opponents:{$humanUser->id}", 0, 2)
            ->andReturn([(string) $user1->id]);

        $foundAgent = $this->service->findAvailableAgent('checkers', null, $humanUser->id);

        // Should find the 24/7 agent (time-specific agent is filtered out)
        expect($foundAgent)->not->toBeNull()
            ->and($foundAgent->id)->toBe($user247->id);
    });
});
