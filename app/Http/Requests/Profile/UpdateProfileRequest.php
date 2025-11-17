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
            'name' => 'sometimes|string|min:3|max:255',
            'bio' => 'sometimes|nullable|string|max:500',
            'social_links' => 'sometimes|nullable|array',
            'social_links.twitter' => 'sometimes|nullable|string|max:255',
            'social_links.website' => 'sometimes|nullable|url|max:255',
            'social_links.discord' => 'sometimes|nullable|string|max:255',
            'social_links.twitch' => 'sometimes|nullable|url|max:255',
        ];

        // Only include username validation rules if username is being sent
        if ($this->has('username')) {
            $rules['username'] = 'sometimes|string|min:3|max:50|unique:users,username,'.$this->user()->id;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a string',
            'name.min' => 'Name must be at least 3 characters',
            'name.max' => 'Name cannot exceed 255 characters',
            'username.string' => 'Username must be a string',
            'username.min' => 'Username must be at least 3 characters',
            'username.max' => 'Username cannot exceed 50 characters',
            'username.unique' => 'This username is already taken',
            'bio.string' => 'Bio must be a string',
            'bio.max' => 'Bio cannot exceed 500 characters',
            'social_links.array' => 'Social links must be an array',
            'social_links.twitter.string' => 'Twitter handle must be a string',
            'social_links.website.url' => 'Website link must be a valid URL',
            'social_links.twitch.url' => 'Twitch link must be a valid URL',
            'social_links.discord.string' => 'Discord handle must be a string',
        ];
    }

    /**
     * Configure the validator instance to check username permission AFTER validation passes.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check username permission ONLY if validation passed and username field exists
            if ($this->has('username') && ! $validator->errors()->has('username')) {
                if (! $this->user() || ! $this->user()->canUpdateUsername()) {
                    // We can't return 403 from validator, so we add error and will handle in controller
                    $validator->errors()->add('_permission_denied', 'username_update_not_allowed');
                }
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // If username permission check failed, return 403 instead of 422
        if ($validator->errors()->has('_permission_denied')) {
            throw new \Illuminate\Validation\ValidationException(
                $validator,
                response()->json([
                    'message' => 'You do not have permission to update your username.',
                ], 403)
            );
        }

        parent::failedValidation($validator);
    }
}
