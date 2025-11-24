<?php

namespace App\Http\Requests\Games;

class ProcessGameActionRequest extends BaseGameRequest
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
            'action_type' => 'required|string',
            'action_details' => 'required|array',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action_type.required' => 'Action type is required',
            'action_type.string' => 'Action type must be a string',
            'action_details.required' => 'Action details are required',
            'action_details.array' => 'Action details must be an array',
        ];
    }
}
