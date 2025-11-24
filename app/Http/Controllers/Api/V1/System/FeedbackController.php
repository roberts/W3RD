<?php

namespace App\Http\Controllers\Api\V1\System;

use App\Enums\FeedbackType;
use App\Http\Controllers\Controller;
use App\Models\System\Feedback;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class FeedbackController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'type' => ['required', new Enum(FeedbackType::class)],
            'content' => ['required', 'string', 'max:5000'],
            'email' => [
                'nullable',
                'email',
                Rule::requiredIf(fn () => $request->user() === null),
            ],
            'metadata' => ['nullable', 'array'],
            'metadata.url' => ['nullable', 'string', 'url'],
            'metadata.user_agent' => ['nullable', 'string'],
            'metadata.app_version' => ['nullable', 'string'],
        ]);

        $feedback = Feedback::create([
            'client_id' => $validated['client_id'],
            'user_id' => $request->user()?->id,
            'email' => $validated['email'] ?? $request->user()?->email,
            'type' => $validated['type'],
            'content' => $validated['content'],
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json($feedback, 201);
    }
}
