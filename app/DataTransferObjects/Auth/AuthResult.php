<?php

namespace App\DataTransferObjects\Auth;

use App\Models\Auth\User;

class AuthResult
{
    public function __construct(
        public bool $success,
        public ?string $token = null,
        public ?User $user = null,
        public ?string $errorMessage = null
    ) {}

    public static function success(string $token, User $user): self
    {
        return new self(
            success: true,
            token: $token,
            user: $user
        );
    }

    public static function failed(string $errorMessage): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage
        );
    }
}
