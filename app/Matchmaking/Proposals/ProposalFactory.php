<?php

declare(strict_types=1);

namespace App\Matchmaking\Proposals;

/**
 * Factory to get the appropriate proposal handler based on type.
 */
class ProposalFactory
{
    /**
     * @var ProposalHandler[]
     */
    private array $handlers = [];

    public function __construct(
        RematchHandler $rematchHandler,
        ChallengeHandler $challengeHandler
    ) {
        $this->handlers = [
            $rematchHandler,
            $challengeHandler,
        ];
    }

    /**
     * Get the handler for a given proposal type.
     */
    public function getHandler(string $type): ProposalHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($type)) {
                return $handler;
            }
        }

        throw new \InvalidArgumentException("No handler found for proposal type: {$type}");
    }
}
