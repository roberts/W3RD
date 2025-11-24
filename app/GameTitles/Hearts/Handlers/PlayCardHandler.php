<?php

declare(strict_types=1);

namespace App\GameTitles\Hearts\Handlers;

use App\Enums\GameErrorCode;
use App\GameEngine\Actions\PlayCard;
use App\GameEngine\Interfaces\GameActionHandlerInterface;
use App\GameEngine\ValidationResult;
use App\GameTitles\Hearts\Enums\HeartsActionError;
use App\GameTitles\Hearts\HeartsPlayer;
use App\GameTitles\Hearts\HeartsTable;

class PlayCardHandler implements GameActionHandlerInterface
{
    public function validate(object $state, object $action): ValidationResult
    {
        if (! ($state instanceof HeartsTable)) {
            return ValidationResult::invalid(GameErrorCode::INVALID_STATE->value, 'State must be Hearts HeartsTable');
        }
        if (! ($action instanceof PlayCard)) {
            return ValidationResult::invalid(GameErrorCode::INVALID_ACTION_TYPE->value, 'Action must be PlayCard');
        }

        // Basic validation: check if player has card
        $hand = $state->hands[$state->currentPlayerUlid] ?? [];
        if (! in_array($action->card, $hand)) {
            return ValidationResult::invalid(HeartsActionError::CARD_NOT_IN_HAND->value, 'You do not have this card');
        }

        $isFirstTrick = count($hand) === 13;
        $isLeading = empty($state->currentTrick);
        $cardSuit = $action->card[0];

        // 1. Must Play 2 of Clubs on First Trick Lead
        if ($isFirstTrick && $isLeading) {
            if ($action->card !== 'C2') {
                return ValidationResult::invalid(HeartsActionError::MUST_PLAY_TWO_OF_CLUBS->value, 'Must lead with Two of Clubs');
            }
        }

        // 2. Must Follow Suit
        if (! $isLeading) {
            $leadCard = $state->currentTrick[$state->trickLeaderUlid];
            $leadSuit = $leadCard[0];

            if ($cardSuit !== $leadSuit) {
                // Check if player has any cards of lead suit
                $hasLeadSuit = false;
                foreach ($hand as $card) {
                    if ($card[0] === $leadSuit) {
                        $hasLeadSuit = true;
                        break;
                    }
                }

                if ($hasLeadSuit) {
                    return ValidationResult::invalid(HeartsActionError::MUST_FOLLOW_SUIT->value, "Must follow suit ($leadSuit)");
                }
            }
        }

        // 3. Points on First Trick
        if ($isFirstTrick) {
            $isPointCard = $cardSuit === 'H' || $action->card === 'SQ';

            // If we are not leading, and we are playing a point card, check if we had other options
            if (! $isLeading && $isPointCard) {
                $leadCard = $state->currentTrick[$state->trickLeaderUlid];
                $leadSuit = $leadCard[0];

                // We already passed MUST_FOLLOW_SUIT check.
                // If we are following suit, we can play points (e.g. if Hearts led? No, Hearts can't be led on first trick).
                // If Clubs led, and we play SQ/Heart, we must be void in Clubs.

                if ($cardSuit !== $leadSuit) {
                    // We are void in lead suit (sloughing).
                    // Check if player has any non-point cards
                    $hasNonPoint = false;
                    foreach ($hand as $card) {
                        if ($card[0] !== 'H' && $card !== 'SQ') {
                            $hasNonPoint = true;
                            break;
                        }
                    }

                    if ($hasNonPoint) {
                        if ($cardSuit === 'H') {
                            return ValidationResult::invalid(HeartsActionError::CANNOT_PLAY_HEARTS_ON_FIRST_TRICK->value, 'Cannot play Hearts on first trick');
                        }
                        if ($action->card === 'SQ') {
                            return ValidationResult::invalid(HeartsActionError::CANNOT_PLAY_QUEEN_ON_FIRST_TRICK->value, 'Cannot play Queen of Spades on first trick');
                        }
                    }
                }
            }
        }

        // 4. Leading Hearts
        if ($isLeading && $cardSuit === 'H' && ! $state->heartsBroken) {
            // Exception: If player has ONLY hearts
            $hasOnlyHearts = true;
            foreach ($hand as $card) {
                if ($card[0] !== 'H') {
                    $hasOnlyHearts = false;
                    break;
                }
            }

            if (! $hasOnlyHearts) {
                return ValidationResult::invalid(HeartsActionError::CANNOT_LEAD_HEARTS->value, 'Cannot lead Hearts until broken');
            }
        }

        return ValidationResult::valid();
    }

    public function apply(object $state, object $action): object
    {
        if (! ($state instanceof HeartsTable) || ! ($action instanceof PlayCard)) {
            return $state;
        }

        // Remove card from player's hand
        $hands = $state->hands;
        $playerHand = $hands[$state->currentPlayerUlid] ?? [];
        $cardIndex = array_search($action->card, $playerHand);

        if ($cardIndex !== false) {
            array_splice($playerHand, $cardIndex, 1);
            $hands[$state->currentPlayerUlid] = $playerHand;
        }

        // Add card to current trick
        $currentTrick = $state->currentTrick;
        $currentTrick[$state->currentPlayerUlid] = $action->card;

        // Check if hearts were broken
        $isHeart = $action->card[0] === 'H';
        $heartsBroken = $state->heartsBroken || $isHeart;

        // Determine trick leader (first player in trick if not set)
        $trickLeaderUlid = $state->trickLeaderUlid ?? $state->currentPlayerUlid;

        // Check if trick is complete (all 4 players have played)
        if (count($currentTrick) === 4) {
            // Determine trick winner
            $trickWinnerUlid = $this->determineTrickWinner($currentTrick, $trickLeaderUlid);

            // Calculate points from this trick
            $trickPoints = $this->calculateTrickPoints($currentTrick);

            // Update player scores
            $players = $state->players;
            $player = $players[$trickWinnerUlid];
            $players[$trickWinnerUlid] = new HeartsPlayer(
                ulid: $player->ulid,
                position: $player->position,
                score: $player->score + $trickPoints,
            );

            // Clear trick and set winner as next leader
            return new HeartsTable(
                players: $players,
                currentPlayerUlid: $trickWinnerUlid,
                winnerUlid: $state->winnerUlid,
                phase: $state->phase,
                status: $state->status,
                roundNumber: $state->roundNumber,
                hands: $hands,
                currentTrick: [], // Clear trick
                trickLeaderUlid: $trickWinnerUlid,
                heartsBroken: $heartsBroken,
                isDraw: $state->isDraw,
            );
        }

        // Move to next player
        return new HeartsTable(
            players: $state->players,
            currentPlayerUlid: $this->getNextPlayerUlid($state),
            winnerUlid: $state->winnerUlid,
            phase: $state->phase,
            status: $state->status,
            roundNumber: $state->roundNumber,
            hands: $hands,
            currentTrick: $currentTrick,
            trickLeaderUlid: $trickLeaderUlid,
            heartsBroken: $heartsBroken,
            isDraw: $state->isDraw,
        );
    }

    /**
     * @param array<string, string> $trick
     */
    private function determineTrickWinner(array $trick, string $leadPlayerUlid): string
    {
        $leadCard = $trick[$leadPlayerUlid];
        $leadSuit = $leadCard[0];

        $highestCard = $leadCard;
        $winnerUlid = $leadPlayerUlid;

        foreach ($trick as $playerUlid => $card) {
            $suit = $card[0];

            // Only cards of the led suit can win
            if ($suit === $leadSuit) {
                if ($this->compareCards($card, $highestCard) > 0) {
                    $highestCard = $card;
                    $winnerUlid = $playerUlid;
                }
            }
        }

        return $winnerUlid;
    }

    /**
     * @param array<string, string> $trick
     */
    private function calculateTrickPoints(array $trick): int
    {
        $points = 0;
        foreach ($trick as $card) {
            if ($card[0] === 'H') {
                $points += 1;
            }
            if ($card === 'SQ') {
                $points += 13;
            }
        }

        return $points;
    }

    private function compareCards(string $card1, string $card2): int
    {
        $ranks = ['2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, 'T' => 10, 'J' => 11, 'Q' => 12, 'K' => 13, 'A' => 14];
        $rank1 = $ranks[$card1[1]] ?? 0;
        $rank2 = $ranks[$card2[1]] ?? 0;

        return $rank1 <=> $rank2;
    }

    private function getNextPlayerUlid(HeartsTable $state): string
    {
        $playerUlids = array_keys($state->players);
        $currentIndex = array_search($state->currentPlayerUlid, $playerUlids);
        $nextIndex = ($currentIndex + 1) % 4;

        return $playerUlids[$nextIndex];
    }

    public function getAvailableOptions(object $state, string $playerUlid): array
    {
        return [];
    }
}
