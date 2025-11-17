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
        return [
            'name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|nullable|string|max:500',
            'social_links' => 'sometimes|nullable|array',
            'social_links.twitter' => 'sometimes|nullable|url|max:255',
            'social_links.website' => 'sometimes|nullable|url|max:255',
            'social_links.discord' => 'sometimes|nullable|string|max:255',
            'social_links.twitch' => 'sometimes|nullable|url|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a string',
            'name.max' => 'Name cannot exceed 255 characters',
            'bio.string' => 'Bio must be a string',
            'bio.max' => 'Bio cannot exceed 500 characters',
            'social_links.array' => 'Social links must be an array',
            'social_links.twitter.url' => 'Twitter link must be a valid URL',
            'social_links.website.url' => 'Website link must be a valid URL',
            'social_links.twitch.url' => 'Twitch link must be a valid URL',
            'social_links.discord.string' => 'Discord handle must be a string',
        ];
    }
}
