<?php

namespace App\Exceptions;

use Exception;

class AgentConfigurationException extends Exception
{
    public function __construct(
        string $message = 'Agent configuration error',
        public readonly ?string $agentClass = null,
        public readonly ?array $context = []
    ) {
        parent::__construct($message);
    }
}
