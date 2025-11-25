<?php

namespace App\DataTransferObjects\Games;

class CoordinatedActionResult
{
    public function __construct(
        public readonly bool $isCoordinated,
        public readonly ?string $coordinationGroup = null,
        public readonly ?int $coordinationSequence = null,
        public readonly bool $coordinationComplete = false,
        public readonly mixed $updatedGameState = null,
    ) {}

    public static function notCoordinated(): self
    {
        return new self(isCoordinated: false);
    }

    public static function coordinated(
        string $coordinationGroup,
        int $coordinationSequence,
        bool $coordinationComplete = false,
        mixed $updatedGameState = null
    ): self {
        return new self(
            isCoordinated: true,
            coordinationGroup: $coordinationGroup,
            coordinationSequence: $coordinationSequence,
            coordinationComplete: $coordinationComplete,
            updatedGameState: $updatedGameState
        );
    }
}
