<?php

namespace App\Http\Requests\Floor;

use Illuminate\Foundation\Http\FormRequest;

class StoreSignalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_title' => 'required|string',
            'game_mode' => 'nullable|string',
            'skill_rating' => 'nullable|integer|min:1|max:5000',
            'preferences' => 'nullable|array',
        ];
    }
}
