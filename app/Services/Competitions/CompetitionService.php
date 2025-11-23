<?php

namespace App\Services\Competitions;

use App\Events\TournamentBracketUpdated;
use App\Exceptions\BusinessRuleException;
use App\Models\Auth\User;
use App\Models\Competitions\Tournament;
use App\Services\Economy\EconomyService;
use Illuminate\Support\Facades\DB;

class CompetitionService
{
    public function __construct(
        protected EconomyService $economyService
    ) {}

    /**
     * Enter a user into a tournament.
     */
    public function enterTournament(Tournament $tournament, User $user): bool
    {
        if (! $tournament->isRegistrationOpen()) {
            throw BusinessRuleException::tournamentRegistrationClosed();
        }

        // Check if already entered
        if ($tournament->users()->where('user_id', $user->id)->exists()) {
            throw BusinessRuleException::alreadyInTournament();
        }

        return DB::transaction(function () use ($tournament, $user) {
            // Reserve buy-in amount if required
            if ($tournament->buy_in_amount > 0) {
                $reserved = $this->economyService->reserveBalance(
                    $user->id,
                    $tournament->buy_in_currency ?? 'chips',
                    $tournament->buy_in_amount
                );

                if (! $reserved) {
                    throw BusinessRuleException::insufficientBalance($tournament->buy_in_currency ?? 'chips');
                }
            }

            // Add user to tournament
            $seed = $tournament->users()->count() + 1;

            $tournament->users()->attach($user->id, [
                'status' => 'registered',
                'seed' => $seed,
            ]);

            return true;
        });
    }

    /**
     * Start tournament and generate bracket.
     */
    public function startTournament(Tournament $tournament): void
    {
        if ($tournament->status !== 'registration_open') {
            throw new \Exception('Tournament must be in registration_open status to start');
        }

        $participants = $tournament->users()->get();

        if ($participants->count() < 2) {
            throw new \Exception('Tournament needs at least 2 participants');
        }

        // Generate bracket based on format
        $bracket = $this->generateBracket($tournament, $participants);

        $tournament->update([
            'status' => 'in_progress',
            'bracket_data' => $bracket,
        ]);

        event(new TournamentBracketUpdated($tournament));
    }

    /**
     * Generate tournament bracket.
     */
    protected function generateBracket(Tournament $tournament, $participants): array
    {
        // Simple single-elimination bracket
        $rounds = [];
        $currentRound = [];

        // Pair up participants
        $participantsList = $participants->shuffle();

        for ($i = 0; $i < $participantsList->count(); $i += 2) {
            if (isset($participantsList[$i + 1])) {
                $currentRound[] = [
                    'player1_id' => $participantsList[$i]->id,
                    'player2_id' => $participantsList[$i + 1]->id,
                    'game_id' => null,
                    'winner_id' => null,
                ];
            } else {
                // Bye - player advances automatically
                $currentRound[] = [
                    'player1_id' => $participantsList[$i]->id,
                    'player2_id' => null,
                    'game_id' => null,
                    'winner_id' => $participantsList[$i]->id,
                ];
            }
        }

        $rounds[] = $currentRound;

        return [
            'format' => 'single_elimination',
            'rounds' => $rounds,
            'current_round' => 0,
        ];
    }

    /**
     * Advance bracket after a game completes.
     */
    public function advanceBracket(Tournament $tournament, int $gameId, int $winnerId): void
    {
        $bracketData = $tournament->bracket_data;

        // Update bracket with game result
        // Find the match and update winner
        // Create next round matches if needed

        // This is simplified - full implementation would handle
        // all bracket advancement logic

        $tournament->update(['bracket_data' => $bracketData]);

        event(new TournamentBracketUpdated($tournament));
    }
}
