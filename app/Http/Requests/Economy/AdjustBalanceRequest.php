<?php

namespace App\Http\Requests\Economy;

use Illuminate\Foundation\Http\FormRequest;

class AdjustBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only approved clients can adjust balances
        // In production, check client_id against whitelist
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'currency_type' => ['required', 'string', 'in:chips,tokens,credits'],
            'amount' => ['required', 'integer'],
            'description' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
