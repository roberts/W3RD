<?php

namespace App\Http\Requests\Games;

class AbandonGameRequest extends BaseGameRequest
{
    public function authorize(): bool
    {
        return $this->authorizeGameAccess() && $this->authorizeActiveGame();
    }

    public function rules(): array
    {
        return [
            // No additional validation needed
        ];
    }
}
