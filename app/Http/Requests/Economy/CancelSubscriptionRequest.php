<?php

namespace App\Http\Requests\Economy;

use Illuminate\Foundation\Http\FormRequest;

class CancelSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'immediately' => 'sometimes|boolean',
            'reason' => 'sometimes|string|in:too_expensive,not_using,found_alternative,technical_issues,other',
            'feedback' => 'sometimes|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.in' => 'Please select a valid cancellation reason.',
            'feedback.max' => 'Feedback cannot exceed 1000 characters.',
        ];
    }
}
