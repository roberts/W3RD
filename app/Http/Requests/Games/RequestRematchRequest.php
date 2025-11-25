<?php

namespace App\Http\Requests\Games;

use App\Enums\GameStatus;

class RequestRematchRequest extends BaseGameRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorize game access, but NOT active game (rematches happen after completion)
        if (! $this->authorizeGameAccess()) {
            return false;
        }

        // Game must be completed to request rematch
        return $this->game()->status === GameStatus::COMPLETED;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // No additional validation needed
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            //
        ];
    }
}
