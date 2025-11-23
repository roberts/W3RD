<?php

namespace App\Http\Requests\Games;

class ViewGameRequest extends BaseGameRequest
{
    /**
     * Authorize that the user is a player in the game.
     * This is used for read-only game endpoints (timeline, sync, outcome, timer).
     */
    public function authorize(): bool
    {
        return $this->authorizeGameAccess();
    }

    public function rules(): array
    {
        return [
            // No additional validation needed for viewing
        ];
    }
}
