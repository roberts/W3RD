<?php

namespace App\Http\Requests\Quickplay;

use Illuminate\Foundation\Http\FormRequest;

class AcceptMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'match_id' => 'required|string|size:26', // ULID is 26 characters
        ];
    }

    public function messages(): array
    {
        return [
            'match_id.required' => 'Match ID is required',
            'match_id.size' => 'Invalid match ID format',
        ];
    }
}
