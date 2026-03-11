<?php

namespace App\Http\Requests\Games;

use Illuminate\Contracts\Validation\ValidationRule;

class ForfeitGameRequest extends BaseGameRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->authorizeGameAccess() && $this->authorizeActiveGame();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
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
