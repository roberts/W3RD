<?php

namespace App\Http\Requests\Rematch;

use App\Models\Game\RematchRequest;
use Illuminate\Foundation\Http\FormRequest;

class AcceptRematchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $rematchRequest = $this->route('requestId');

        if (! $rematchRequest instanceof RematchRequest) {
            return false;
        }

        // User must be the opponent (not the requester)
        if ($rematchRequest->opponent_user_id !== $this->user()->id) {
            return false;
        }

        // Request must be pending
        if ($rematchRequest->status !== 'pending') {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // No additional validation needed
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            //
        ];
    }
}
