<?php

namespace App\Games\ValidateFour\Actions;

class PopOut
{
    public readonly int $column;

    /**
     * Create a new PopOut action.
     *
     * @param array $data Must contain 'column' key with integer value
     * @throws \InvalidArgumentException if data is invalid
     */
    public function __construct(array $data)
    {
        if (!isset($data['column'])) {
            throw new \InvalidArgumentException('PopOut action requires "column" field.');
        }

        if (!is_int($data['column']) && !is_numeric($data['column'])) {
            throw new \InvalidArgumentException('Column must be an integer.');
        }

        $this->column = (int) $data['column'];

        if ($this->column < 0) {
            throw new \InvalidArgumentException('Column must be non-negative.');
        }
    }
}
