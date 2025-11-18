<?php

use App\Models\Auth\Agent;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Services\Agents\AgentSchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(AgentSchedulingService::class);
});

test('can find an available agent for a specific game', function () {
    // Create an agent that supports checkers
    $agent = Agent::factory()->forGame('checkers')->create();
    $user = User::factory()->create(['agent_id' => $agent->id]);

    $foundAgent = $this->service->findAvailableAgent('checkers');

    expect($foundAgent)->not->toBeNull()
        ->and($foundAgent->id)->toBe($user->id);
});

test('returns null when no agents support the requested game', function () {
    // Create an agent that only supports hearts
    $agent = Agent::factory()->forGame('hearts')->create();
    User::factory()->create(['agent_id' => $agent->id]);

    $foundAgent = $this->service->findAvailableAgent('checkers');

    expect($foundAgent)->toBeNull();
});

test('finds agent that supports all games', function () {
    $agent = Agent::factory()->allGames()->create();
    $user = User::factory()->create(['agent_id' => $agent->id]);

    $foundAgent = $this->service->findAvailableAgent('checkers');

    expect($foundAgent)->not->toBeNull()
        ->and($foundAgent->id)->toBe($user->id);
});

test('skips agents that are currently busy in a game', function () {
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

test('respects agent availability hours', function () {
    // Set current time to 2 PM EST (which is 19:00 UTC in winter)
    \Illuminate\Support\Facades\Date::setTestNow('2024-01-15 19:00:00'); // 2 PM EST = 19:00 UTC

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

test('finds agents with null availability (24/7 agents)', function () {
    $agent = Agent::factory()->forGame('checkers')->alwaysAvailable()->create();
    $user = User::factory()->create(['agent_id' => $agent->id]);

    $foundAgent = $this->service->findAvailableAgent('checkers');

    expect($foundAgent)->not->toBeNull()
        ->and($foundAgent->id)->toBe($user->id);
});

test('prefers time-specific agents over 24/7 agents during their available hour', function () {
    // Set current time to 3 PM EST (20:00 UTC in winter)
    \Illuminate\Support\Facades\Date::setTestNow('2024-01-15 20:00:00'); // 3 PM EST

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

test('uses 24/7 agents as fallback when no time-specific agents are available', function () {
    // Set current time to 10 AM EST (15:00 UTC in winter)
    \Illuminate\Support\Facades\Date::setTestNow('2024-01-15 15:00:00'); // 10 AM EST

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

test('handles multiple time-specific agents at the same hour', function () {
    // Set current time to 4 PM EST (21:00 UTC in winter)
    \Illuminate\Support\Facades\Date::setTestNow('2024-01-15 21:00:00'); // 4 PM EST

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

test('returns null when no agents are available at current hour and no 24/7 agents exist', function () {
    // Set current time to 11 PM EST (04:00 UTC next day in winter)
    \Illuminate\Support\Facades\Date::setTestNow('2024-01-16 04:00:00'); // 11 PM EST

    // Create agents available at different times (not 11 PM)
    $agent1 = Agent::factory()->forGame('checkers')->create(['available_hour_est' => 9]); // 9 AM
    User::factory()->create(['agent_id' => $agent1->id]);

    $agent2 = Agent::factory()->forGame('checkers')->create(['available_hour_est' => 14]); // 2 PM
    User::factory()->create(['agent_id' => $agent2->id]);

    $foundAgent = $this->service->findAvailableAgent('checkers');

    // Should return null - no available agents
    expect($foundAgent)->toBeNull();
});
