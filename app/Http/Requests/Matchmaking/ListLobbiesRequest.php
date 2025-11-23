<?php

namespace App\Http\Requests\Matchmaking;

use App\Enums\GameTitle;
use App\Matchmaking\Enums\LobbyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListLobbiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_title' => ['sometimes', 'string', Rule::in(array_column(GameTitle::cases(), 'value'))],
            'status' => ['sometimes', 'string', Rule::in(array_column(LobbyStatus::cases(), 'value'))],
            'is_public' => 'sometimes|boolean',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'game_title.in' => 'Invalid game title.',
            'status.in' => 'Invalid lobby status.',
            'per_page.max' => 'Cannot request more than 100 items per page.',
        ];
    }
}
