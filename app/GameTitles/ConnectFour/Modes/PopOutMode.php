<?php

namespace App\GameTitles\ConnectFour\Modes;

use App\GameTitles\ConnectFour\Actions\PopOut;
use App\GameTitles\ConnectFour\ConnectFourArbiter;
use App\GameTitles\ConnectFour\ConnectFourConfig;
use App\GameTitles\ConnectFour\ConnectFourProtocol;
use App\GameTitles\ConnectFour\ConnectFourReporter;
use App\GameTitles\ConnectFour\Handlers\PopOutHandler;

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

    /**
     * @return array<string, mixed>
     */
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
