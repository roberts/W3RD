<?php

namespace App\Games\ConnectFour\Modes;

use App\Games\ConnectFour\Actions\PopOut;
use App\Games\ConnectFour\ConnectFourArbiter;
use App\Games\ConnectFour\ConnectFourConfig;
use App\Games\ConnectFour\ConnectFourProtocol;
use App\Games\ConnectFour\ConnectFourReporter;
use App\Games\ConnectFour\Handlers\PopOutHandler;

class PopOutMode extends ConnectFourProtocol
{
    protected function getGameConfig(): ConnectFourConfig
    {
        return new ConnectFourConfig(
            additionalActions: [
                PopOut::class => [
                    'handler' => PopOutHandler::class,
                    'label' => 'Pop Out',
                ],
            ]
        );
    }

    public function getArbiter(): ConnectFourArbiter
    {
        return new ConnectFourArbiter;
    }

    protected function getReporter(): ConnectFourReporter
    {
        return new ConnectFourReporter;
    }

    public static function getRules(): array
    {
        $baseRules = parent::getRules();

        $popOutRules = [
            'name' => 'Pop Out',
            'description' => 'A variant where you can remove a piece from the bottom row instead of dropping one.',
            'sections' => [
                [
                    'title' => 'Special Rule: Popping Out',
                    'content' => <<<'MARKDOWN'
                    On your turn, you may choose to **pop out** one of your own pieces from the **bottom row**.

                    *   This removes the piece from the board.
                    *   All pieces in the column above it will fall down one space.
                    *   You cannot pop a piece if it is the only one in its column.
                    MARKDOWN,
                ],
            ],
        ];

        // Merge the Pop-Out rules into the base rules
        $baseRules['sections'] = array_merge($baseRules['sections'], $popOutRules['sections']);
        $baseRules['description'] = $popOutRules['description'];
        $baseRules['name'] = $popOutRules['name'];

        return $baseRules;
    }
}
