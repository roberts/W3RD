<?php

use App\Jobs\CalculateAgentAction;
use App\Models\Auth\Agent;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Services\Agents\AgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(AgentService::class);
    Queue::fake();
});

describe('Agent Action Handling', function () {
    it('dispatches CalculateAgentAction job when performing action', function () {
        $agent = Agent::factory()->create();
        $user = User::factory()->create(['agent_id' => $agent->id]);

        // Create a real game for testing
        $game = Game::factory()->create();

        $this->service->performAction($user, $game);

        Queue::assertPushed(CalculateAgentAction::class);
    });

    it('throws exception if user is not an agent', function () {
        $user = User::factory()->create(['agent_id' => null]);
        $game = Game::factory()->create();

        expect(fn () => $this->service->performAction($user, $game))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('is dispatched with correct parameters', function () {
        $agent = Agent::factory()->withDifficulty(7)->create();
        $user = User::factory()->create(['agent_id' => $agent->id]);
        $game = Game::factory()->create();

        $this->service->performAction($user, $game);

        Queue::assertPushed(CalculateAgentAction::class, function ($job) use ($user) {
            return $job->user->id === $user->id;
        });
    });
});
