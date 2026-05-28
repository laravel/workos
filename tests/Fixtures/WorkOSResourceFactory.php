<?php

namespace Tests\Fixtures;

class WorkOSResourceFactory
{
    public static function userArray(array $overrides = []): array
    {
        return array_merge([
            'object' => 'user',
            'id' => 'user_123',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'profile_picture_url' => 'https://example.com/avatar.jpg',
            'email' => 'old@example.com',
            'email_verified' => true,
            'external_id' => null,
            'last_sign_in_at' => null,
            'created_at' => '2026-05-19T11:00:00.000Z',
            'updated_at' => '2026-05-19T12:00:00.000Z',
            'metadata' => null,
            'locale' => null,
        ], $overrides);
    }
}
