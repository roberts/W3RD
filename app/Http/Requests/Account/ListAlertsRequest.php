<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class ListAlertsRequest extends FormRequest
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
            'read' => 'sometimes|boolean',
            'type' => 'sometimes|string|in:game_invite,rematch_request,game_completed,tournament_invite,achievement,system',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
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
            'type.in' => 'Invalid alert type.',
            'date_to.after_or_equal' => 'End date must be after or equal to start date.',
            'per_page.max' => 'Cannot request more than 100 items per page.',
        ];
    }
}
