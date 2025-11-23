<?php

namespace App\Http\Requests\Matchmaking;

use App\Models\Matchmaking\QueueSlot;
use Illuminate\Foundation\Http\FormRequest;

class CancelQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $slot = $this->route('slot');

        if ($slot instanceof QueueSlot) {
            return $slot->user_id === $this->user()->id;
        }

        return false;
    }

    public function rules(): array
    {
        return [
            // No body parameters needed
        ];
    }

    public function messages(): array
    {
        return [
            //
        ];
    }
}
