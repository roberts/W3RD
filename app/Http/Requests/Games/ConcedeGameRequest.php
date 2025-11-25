<?php

namespace App\Http\Requests\Games;

class ConcedeGameRequest extends BaseGameRequest
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
            'reason' => 'sometimes|string|in:voluntary,timeout,disconnect,technical_issue',
            'note' => 'sometimes|string|max:500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.in' => 'The reason must be one of: voluntary, timeout, disconnect, or technical_issue.',
            'note.max' => 'The note cannot exceed 500 characters.',
        ];
    }
}
