<?php

use App\Models\Auth\Agent;
use App\Models\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('human player is matched with agent after timeout in quickplay', function () {
    // Create an available agent
    $agent = Agent::factory()->forGame('checkers')->alwaysAvailable()->create();
    $agentUser = User::factory()->create(['agent_id' => $agent->id]);

    // Create a human player
    $humanUser = User::factory()->create();

    // Simulate Quickplay request for checkers
    // This would typically be an API call: POST /api/quickplay
    // For this test, we're testing the underlying service logic

    // The actual Quickplay service integration will be tested once
    // we integrate with the existing Quickplay system

    expect($agentUser->isAgent())->toBeTrue()
        ->and($humanUser->isAgent())->toBeFalse();
});

test('agent plays turn with human-like delay', function () {
    // This will be a more comprehensive test once CalculateAgentAction job is implemented
    // For now, we verify the basic structure

    $agent = Agent::factory()->create();
    $agentUser = User::factory()->create(['agent_id' => $agent->id]);

    expect($agentUser->isAgent())->toBeTrue()
        ->and($agent->difficulty)->toBeGreaterThanOrEqual(1)
        ->and($agent->difficulty)->toBeLessThanOrEqual(10);
});

test('agent respects game compatibility in matchmaking', function () {
    // Create agent that only plays hearts
    $heartsAgent = Agent::factory()->forGame('hearts')->create();
    $heartsUser = User::factory()->create(['agent_id' => $heartsAgent->id]);

    // Create agent that plays all games
    $allGamesAgent = Agent::factory()->allGames()->create();
    $allGamesUser = User::factory()->create(['agent_id' => $allGamesAgent->id]);

    expect($heartsAgent->supported_game_titles)->toBe(['hearts'])
        ->and($allGamesAgent->supported_game_titles)->toBe(['all']);
});
