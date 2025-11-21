<?php

namespace App\Services\Floor;

use App\Enums\GameTitle;
use App\Events\Floor\ProposalSent;
use App\Exceptions\BusinessRuleException;
use App\Models\Auth\User;
use App\Models\Game\Proposal;
use App\Models\MatchmakingSignal;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class FloorCoordinationService
{
    public function createSignal(User $user, GameTitle $gameTitle, ?string $gameMode, array $preferences = [], ?int $skillRating = null): MatchmakingSignal
    {
        $ttl = (int) config('protocol.floor.matchmaking.signal_ttl_minutes', 5);

        $preferences = array_merge(
            ['game_mode' => $gameMode ?? 'standard'],
            $preferences
        );

        $signal = MatchmakingSignal::updateOrCreate(
            ['user_id' => $user->id],
            [
                'game_preference' => $gameTitle->value,
                'skill_rating' => $skillRating,
                'status' => 'active',
                'preferences' => $preferences,
                'expires_at' => Carbon::now()->addMinutes($ttl),
            ]
        );

        return $signal;
    }

    public function cancelSignal(MatchmakingSignal $signal): MatchmakingSignal
    {
        $signal->update([
            'status' => 'cancelled',
            'expires_at' => Carbon::now(),
        ]);

        return $signal;
    }

    public function createProposal(User $requestingUser, User $opponentUser, array $payload): Proposal
    {
        $maxActive = (int) config('protocol.floor.proposals.max_active_per_user', 5);
        $activeCount = Proposal::where('requesting_user_id', $requestingUser->id)
            ->where('status', 'pending')
            ->count();

        if ($activeCount >= $maxActive) {
            throw BusinessRuleException::maxProposalsExceeded();
        }

        $expirationMinutes = (int) config('protocol.floor.proposals.expiration_minutes', 5);

        $proposal = Proposal::create([
            'requesting_user_id' => $requestingUser->id,
            'opponent_user_id' => $opponentUser->id,
            'title_slug' => Arr::get($payload, 'title_slug'),
            'mode_id' => Arr::get($payload, 'mode_id'),
            'type' => Arr::get($payload, 'type', 'rematch'),
            'original_game_id' => Arr::get($payload, 'original_game_id'),
            'game_settings' => Arr::get($payload, 'game_settings'),
            'status' => 'pending',
            'expires_at' => Carbon::now()->addMinutes($expirationMinutes),
        ]);

        event(new ProposalSent($proposal));

        return $proposal;
    }
}
