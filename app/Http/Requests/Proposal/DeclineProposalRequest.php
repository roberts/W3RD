<?php

namespace App\Http\Requests\Proposal;

use App\Models\Matchmaking\Proposal;
use Illuminate\Foundation\Http\FormRequest;

class DeclineProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Proposal|null $proposal */
        $proposal = $this->route('proposal');

        if (! $proposal instanceof Proposal) {
            return false;
        }

        return $proposal->opponent_user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [];
    }
}
