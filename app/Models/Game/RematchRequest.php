<?php

namespace App\Models\Game;

class RematchRequest extends Proposal
{
    /**
     * Backwards compatibility shim for legacy references.
     */
    protected $table = 'proposals';
}
