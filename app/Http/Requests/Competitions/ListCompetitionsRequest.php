<?php

namespace App\Http\Requests\Competitions;

use App\Enums\GameTitle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCompetitionsRequest extends FormRequest
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
            'status' => 'sometimes|string|in:upcoming,active,completed,cancelled',
            'game_title' => ['sometimes', 'string', Rule::in(array_column(GameTitle::cases(), 'value'))],
            'format' => 'sometimes|string|in:single_elimination,double_elimination,round_robin,swiss',
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
            'status.in' => 'Status must be one of: upcoming, active, completed, or cancelled.',
            'game_title.in' => 'Invalid game title.',
            'format.in' => 'Invalid tournament format.',
            'per_page.max' => 'Cannot request more than 100 items per page.',
        ];
    }
}
