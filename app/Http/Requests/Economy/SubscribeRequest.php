<?php

namespace App\Http\Requests\Economy;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan' => 'required|string|in:pro,elite',
            'payment_method' => 'nullable|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan.required' => 'Subscription plan is required',
            'plan.string' => 'Subscription plan must be a string',
            'plan.in' => 'Invalid subscription plan selected',
        ];
    }
}
