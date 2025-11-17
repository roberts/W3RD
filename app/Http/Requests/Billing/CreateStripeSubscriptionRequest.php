<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class CreateStripeSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'plan.required' => 'Subscription plan is required',
            'plan.string' => 'Subscription plan must be a string',
        ];
    }
}
