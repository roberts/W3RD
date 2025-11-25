<?php

namespace App\Http\Requests\Games;

use App\Enums\GameStatus;
use App\Enums\GameTitle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListGamesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(array_column(GameStatus::cases(), 'value'))],
            'game_title' => ['sometimes', 'string', Rule::in(array_column(GameTitle::cases(), 'value'))],
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'opponent_username' => 'sometimes|string|max:50',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Invalid game status.',
            'game_title.in' => 'Invalid game title.',
            'date_to.after_or_equal' => 'End date must be after or equal to start date.',
            'per_page.max' => 'Cannot request more than 100 items per page.',
        ];
    }
}
