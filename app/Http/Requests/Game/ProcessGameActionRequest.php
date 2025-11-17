<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class ProcessGameActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action_type' => 'required|string',
            'action_details' => 'required|array',
        ];
    }

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
