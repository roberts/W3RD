<?php

namespace App\Http\Requests\Games;

class AbandonGameRequest extends BaseGameRequest
{
    public function authorize(): bool
    {
        return $this->authorizeGameAccess() && $this->authorizeActiveGame();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // No additional validation needed
        ];
    }
}
