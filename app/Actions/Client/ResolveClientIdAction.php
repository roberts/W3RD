<?php

namespace App\Actions\Client;

use Illuminate\Http\Request;

class ResolveClientIdAction
{
    /**
     * Resolve the client ID from the request header.
     *
     * Defaults to 1 (Gamer Protocol Web) for AI agents or when header is missing.
     *
     * @return int The client ID (defaults to 1)
     */
    public function execute(Request $request): int
    {
        return (int) $request->header('X-Client-Key') ?: 1;
    }
}
