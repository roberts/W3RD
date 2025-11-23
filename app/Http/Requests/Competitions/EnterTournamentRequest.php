<?php

namespace App\Http\Requests\Competitions;

use Illuminate\Foundation\Http\FormRequest;

class EnterTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in service layer
    }

    public function rules(): array
    {
        return [
            'team_id' => 'sometimes|integer|exists:teams,id',
            'payment_method' => 'sometimes|string|in:balance,stripe,apple,google',
            'agreed_to_rules' => 'required|boolean|accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'team_id.exists' => 'The specified team does not exist.',
            'payment_method.in' => 'Invalid payment method. Must be one of: balance, stripe, apple, or google.',
            'agreed_to_rules.accepted' => 'You must agree to the tournament rules to enter.',
        ];
    }
}
