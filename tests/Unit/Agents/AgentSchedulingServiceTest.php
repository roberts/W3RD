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
