<?php

namespace App\Http\Requests\Quickplay;

use Illuminate\Foundation\Http\FormRequest;

class JoinQuickplayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_title' => 'required|string',
            'game_mode' => 'nullable|string|in:standard,blitz,rapid',
            'skill_rating' => 'nullable|integer|min:1|max:5000',
            'preferences' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'game_title.required' => 'Game title is required',
            'game_mode.in' => 'Game mode must be one of: standard, blitz, rapid',
        ];
    }
}
