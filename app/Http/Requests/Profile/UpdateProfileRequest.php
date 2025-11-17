<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|nullable|string|max:500',
            'social_links' => 'sometimes|nullable|array',
            'social_links.twitter' => 'sometimes|nullable|url|max:255',
            'social_links.website' => 'sometimes|nullable|url|max:255',
            'social_links.discord' => 'sometimes|nullable|string|max:255',
            'social_links.twitch' => 'sometimes|nullable|url|max:255',
        ];

        // Only allow username updates if user has permission
        if ($this->user() && $this->user()->canUpdateUsername()) {
            $rules['username'] = 'sometimes|string|min:3|max:50|unique:users,username,' . $this->user()->id;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a string',
            'name.max' => 'Name cannot exceed 255 characters',
            'username.string' => 'Username must be a string',
            'username.min' => 'Username must be at least 3 characters',
            'username.max' => 'Username cannot exceed 50 characters',
            'username.unique' => 'This username is already taken',
            'bio.string' => 'Bio must be a string',
            'bio.max' => 'Bio cannot exceed 500 characters',
            'social_links.array' => 'Social links must be an array',
            'social_links.twitter.url' => 'Twitter link must be a valid URL',
            'social_links.website.url' => 'Website link must be a valid URL',
            'social_links.twitch.url' => 'Twitch link must be a valid URL',
            'social_links.discord.string' => 'Discord handle must be a string',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // Check if username was attempted without permission
        if ($this->has('username') && (!$this->user() || !$this->user()->canUpdateUsername())) {
            throw new \Illuminate\Validation\ValidationException(
                $validator,
                response()->json([
                    'message' => 'You do not have permission to update your username.',
                    'errors' => [
                        'username' => ['You must have Master Player status to change your username.']
                    ]
                ], 403)
            );
        }

        parent::failedValidation($validator);
    }
}
