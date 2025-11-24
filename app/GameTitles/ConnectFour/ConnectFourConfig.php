<?php

declare(strict_types=1);

namespace App\GameTitles\ConnectFour;

use App\GameEngine\Actions\PlacePiece;
use App\GameEngine\Handlers\PlacePieceHandler;
use App\GameEngine\Interfaces\GameConfigContract;

class ConnectFourConfig implements GameConfigContract
{
    /**
     * @param  array<class-string, array<string, mixed>>  $additionalActions
     * @param  array<string, mixed>  $stateConfig
     */
    public function __construct(
        protected array $additionalActions = [],
        protected array $stateConfig = ['columns' => 7, 'rows' => 6, 'connectCount' => 4]
    ) {}

    /**
     * @return array<class-string, array<string, mixed>>
     */
    public function getActionRegistry(): array
    {
        return array_merge([
            PlacePiece::class => [
                'handler' => PlacePieceHandler::class,
                'label' => 'Drop Disc',
                'rules' => ['gravity' => true],
            ],
        ], $this->additionalActions);
    }

    /**
     * @return array<string, mixed>
     */
    public function getInitialStateConfig(): array
    {
        return $this->stateConfig;
    }
}
