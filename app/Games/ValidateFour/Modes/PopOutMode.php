<?php

namespace App\Games\ValidateFour\Modes;

use App\Games\ValidateFour\Actions\PopOut;
use App\Games\ValidateFour\Handlers\PopOutHandler;
use App\Games\ValidateFour\ValidateFourArbiter;
use App\Games\ValidateFour\ValidateFourConfig;
use App\Games\ValidateFour\ValidateFourProtocol;
use App\Games\ValidateFour\ValidateFourReporter;

class PopOutMode extends ValidateFourProtocol
{
    protected function getGameConfig(): ValidateFourConfig
    {
        return new ValidateFourConfig(
            additionalActions: [
                PopOut::class => [
                    'handler' => PopOutHandler::class,
                    'label' => 'Pop Out',
                ],
            ]
        );
    }

    public function getArbiter(): ValidateFourArbiter
    {
        return new ValidateFourArbiter;
    }

    protected function getReporter(): ValidateFourReporter
    {
        return new ValidateFourReporter;
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
