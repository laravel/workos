<?php

namespace Laravel\WorkOS;

class User
{
    public function __construct(
        public string $id,
        public ?string $firstName,
        public ?string $lastName,
        public string $email,
        public ?string $organizationId,
        public ?string $avatar = null,
    ) {}
}
