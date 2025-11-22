<?php

use App\Enums\GameTitle;
use App\Jobs\CheckAndCancelPendingProposals;
use App\Models\Auth\User;
use App\Models\Matchmaking\QueueSlot;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Redis;

// Matchmaking queue slots provide a normalized interface for joining/cancelling matchmaking queues.
describe('Matchmaking Queue API', function () {
    beforeEach(function () {
        Bus::fake();

        Redis::shouldReceive('setex')->andReturnTrue()->byDefault();
        Redis::shouldReceive('get')->andReturn(null)->byDefault();
        Redis::shouldReceive('expire')->andReturnTrue()->byDefault();
        Redis::shouldReceive('del')->andReturnTrue()->byDefault();
        Redis::shouldReceive('hdel')->andReturnTrue()->byDefault();
        Redis::shouldReceive('exists')->andReturnFalse()->byDefault();
        Redis::shouldReceive('hset')->andReturnTrue()->byDefault();
        Redis::shouldReceive('hgetall')->andReturn([])->byDefault();
        Redis::shouldReceive('zadd')->andReturn(1)->byDefault();
        Redis::shouldReceive('zrem')->andReturn(1)->byDefault();
    });

    it('requires authentication to join queue', function () {
        $response = $this->postJson('/api/v1/matchmaking/queue', [
            'game_title' => GameTitle::CONNECT_FOUR->value,
        ]);

        $response->assertUnauthorized();
    });

    it('creates a queue slot with default mode', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/matchmaking/queue', [
            'game_title' => GameTitle::CONNECT_FOUR->value,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.title_slug', GameTitle::CONNECT_FOUR->value)
            ->assertJsonPath('data.game_mode', 'standard')
            ->assertJsonPath('data.status', 'active');

        Bus::assertDispatched(CheckAndCancelPendingProposals::class);
    });

    it('stores preferences and skill rating when provided', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/matchmaking/queue', [
            'game_title' => GameTitle::CHECKERS->value,
            'game_mode' => 'blitz',
            'skill_rating' => 1850,
            'preferences' => [
                'region' => 'na-east',
                'ready_check' => true,
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.skill_rating', 1850)
            ->assertJsonPath('data.game_mode', 'blitz')
            ->assertJsonPath('data.preferences.region', 'na-east')
            ->assertJsonPath('data.preferences.ready_check', true);
    });

    it('rejects unsupported game titles', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/matchmaking/queue', [
            'game_title' => 'holographic-chess',
        ]);

        $response->assertStatus(422);
    });

    it('prevents joining when cooldown is active', function () {
        $user = User::factory()->create();
        $cooldownKey = "cooldown:queue:{$user->id}";

        Redis::shouldReceive('exists')
            ->with($cooldownKey)
            ->andReturnTrue();
        Redis::shouldReceive('ttl')
            ->with($cooldownKey)
            ->andReturn(180);

        $response = $this->actingAs($user)->postJson('/api/v1/matchmaking/queue', [
            'game_title' => GameTitle::CONNECT_FOUR->value,
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('errors.cooldown_remaining', 180)
            ->assertJsonPath('errors.retry_after', 180)
            ->assertHeader('Retry-After', '180');
    });

    it('allows a player to cancel their own queue slot', function () {
        $user = User::factory()->create();
        $slot = QueueSlot::factory()->for($user)->create([
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/matchmaking/queue/{$slot->ulid}");

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJson(['message' => 'Queue slot cancelled']);

        $slot->refresh();
        expect($slot->status)->toBe('cancelled');
    });

    it('prevents cancelling another players queue slot', function () {
        [$owner, $otherUser] = User::factory()->count(2)->create();
        $slot = QueueSlot::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)
            ->deleteJson("/api/v1/matchmaking/queue/{$slot->ulid}");

        $response->assertForbidden();
    });
});
