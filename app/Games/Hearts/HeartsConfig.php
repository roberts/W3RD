<?php

declare(strict_types=1);

namespace App\Games\Hearts;

use App\GameEngine\Actions\ClaimRemainingTricks;
use App\GameEngine\Actions\PassCards;
use App\GameEngine\Actions\PlayCard;
use App\GameEngine\Interfaces\GameConfigContract;
use App\Games\Hearts\Actions\DealCards;
use App\Games\Hearts\Handlers\ClaimRemainingTricksHandler;
use App\Games\Hearts\Handlers\DealCardsHandler;
use App\Games\Hearts\Handlers\PassCardsHandler;
use App\Games\Hearts\Handlers\PlayCardHandler;

class HeartsConfig implements GameConfigContract
{
    public function getActionRegistry(): array
    {
        return [
            DealCards::class => [
                'handler' => DealCardsHandler::class,
                'label' => 'Deal Cards',
            ],
            PlayCard::class => [
                'handler' => PlayCardHandler::class,
                'label' => 'Play Card',
            ],
            PassCards::class => [
                'handler' => PassCardsHandler::class,
                'label' => 'Pass Cards',
            ],
            ClaimRemainingTricks::class => [
                'handler' => ClaimRemainingTricksHandler::class,
                'label' => 'Claim Remaining Tricks',
            ],
        ];
    }

    public function getInitialStateConfig(): array
    {
        return [];
    }
}
