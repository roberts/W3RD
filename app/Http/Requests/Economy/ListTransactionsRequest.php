<?php

namespace App\Http\Requests\Economy;

use Illuminate\Foundation\Http\FormRequest;

class ListTransactionsRequest extends FormRequest
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
            'currency' => 'sometimes|string|in:credits,gems,coins,tokens',
            'type' => 'sometimes|string|in:purchase,reward,refund,adjustment,transfer',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'min_amount' => 'sometimes|integer|min:0',
            'max_amount' => 'sometimes|integer|min:0',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'currency.in' => 'Invalid currency type.',
            'type.in' => 'Invalid transaction type.',
            'date_to.after_or_equal' => 'End date must be after or equal to start date.',
            'per_page.max' => 'Cannot request more than 100 items per page.',
        ];
    }
}
