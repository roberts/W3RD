<?php

namespace App\DataTransferObjects\Account;

use Spatie\LaravelData\Data;

class ProfileData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $username,
        public ?string $bio,
        public ?string $avatar,
        public ?array $social_links,
        public string $created_at,
    ) {}
}
